from __future__ import annotations

from dataclasses import dataclass
from pathlib import Path
from typing import Any
from zoneinfo import ZoneInfo, ZoneInfoNotFoundError

from google.analytics.admin_v1alpha import AnalyticsAdminServiceClient as AlphaAnalyticsAdminClient
from google.analytics.admin_v1alpha.types import AccessBinding, EnhancedMeasurementSettings
from google.analytics.admin_v1beta import AnalyticsAdminServiceClient as BetaAnalyticsAdminClient
from google.analytics.admin_v1beta.types import DataStream, Property
from google.oauth2 import service_account
from google.protobuf.field_mask_pb2 import FieldMask

from .config import ConfigError


ANALYTICS_READONLY_SCOPE = "https://www.googleapis.com/auth/analytics.readonly"
ANALYTICS_EDIT_SCOPE = "https://www.googleapis.com/auth/analytics.edit"
ANALYTICS_MANAGE_USERS_READONLY_SCOPE = (
    "https://www.googleapis.com/auth/analytics.manage.users.readonly"
)
ANALYTICS_MANAGE_USERS_SCOPE = "https://www.googleapis.com/auth/analytics.manage.users"

ADMIN_PROPERTY_READ_SCOPES = [ANALYTICS_READONLY_SCOPE]
ADMIN_ACCESS_READ_SCOPES = [ANALYTICS_MANAGE_USERS_READONLY_SCOPE]
ADMIN_PROPERTY_EDIT_SCOPES = [ANALYTICS_EDIT_SCOPE]
ADMIN_ACCESS_EDIT_SCOPES = [ANALYTICS_MANAGE_USERS_SCOPE]


@dataclass(frozen=True)
class TimezoneUpdatePreview:
    property_name: str
    current_time_zone: str
    requested_time_zone: str
    apply: bool


def _build_credentials(credentials_path: Path | None, scopes: list[str]):
    if credentials_path is None:
        return None

    return service_account.Credentials.from_service_account_file(
        str(credentials_path),
        scopes=scopes,
    )


def build_beta_admin_client(
    credentials_path: Path | None = None,
    scopes: list[str] | None = None,
) -> BetaAnalyticsAdminClient:
    credentials = _build_credentials(credentials_path, scopes or ADMIN_PROPERTY_READ_SCOPES)
    if credentials is None:
        return BetaAnalyticsAdminClient()
    return BetaAnalyticsAdminClient(credentials=credentials)


def build_alpha_admin_client(
    credentials_path: Path | None = None,
    scopes: list[str] | None = None,
) -> AlphaAnalyticsAdminClient:
    credentials = _build_credentials(credentials_path, scopes or ADMIN_PROPERTY_READ_SCOPES)
    if credentials is None:
        return AlphaAnalyticsAdminClient()
    return AlphaAnalyticsAdminClient(credentials=credentials)


def property_resource_name(property_id: str) -> str:
    return f"properties/{property_id}"


def validate_timezone(time_zone: str) -> str:
    normalized = time_zone.strip()
    if not normalized:
        raise ConfigError("Timezone is required.")

    try:
        ZoneInfo(normalized)
    except ZoneInfoNotFoundError as exc:
        raise ConfigError(f"Unknown IANA timezone: {normalized}") from exc

    return normalized


def normalize_stream_name(stream: str, property_id: str) -> str:
    normalized = stream.strip()
    if not normalized:
        raise ConfigError("Data stream id or resource name is required.")

    if normalized.startswith("properties/"):
        try:
            parsed = BetaAnalyticsAdminClient.parse_data_stream_path(normalized)
        except ValueError as exc:
            raise ConfigError(
                "Data stream must be in the form 'properties/<property>/dataStreams/<stream>'."
            ) from exc

        if parsed["property"] != property_id:
            raise ConfigError(
                f"Data stream {normalized} does not belong to property {property_id}."
            )
        return normalized

    if not normalized.isdigit():
        raise ConfigError(
            "Data stream must be numeric or in the form "
            "'properties/<property>/dataStreams/<stream>'."
        )

    return BetaAnalyticsAdminClient.data_stream_path(property_id, normalized)


def message_to_dict(message: Any) -> dict[str, Any]:
    return type(message).to_dict(
        message,
        use_integers_for_enums=False,
        preserving_proto_field_name=True,
    )


def inspect_property(
    property_id: str,
    credentials_path: Path | None = None,
) -> Property:
    client = build_beta_admin_client(credentials_path, scopes=ADMIN_PROPERTY_READ_SCOPES)
    return client.get_property(name=property_resource_name(property_id))


def list_data_streams(
    property_id: str,
    credentials_path: Path | None = None,
) -> list[DataStream]:
    client = build_beta_admin_client(credentials_path, scopes=ADMIN_PROPERTY_READ_SCOPES)
    pager = client.list_data_streams(parent=property_resource_name(property_id))
    return list(pager)


def _web_streams(streams: list[DataStream]) -> list[DataStream]:
    return [
        stream
        for stream in streams
        if stream.type_ == DataStream.DataStreamType.WEB_DATA_STREAM
    ]


def resolve_web_stream_name(
    property_id: str,
    credentials_path: Path | None = None,
    stream: str | None = None,
) -> str:
    if stream:
        return normalize_stream_name(stream, property_id)

    web_streams = _web_streams(list_data_streams(property_id, credentials_path))
    if not web_streams:
        raise ConfigError(f"Property {property_id} has no web data streams.")

    if len(web_streams) > 1:
        available = ", ".join(
            f"{stream.display_name or stream.name} ({stream.name})" for stream in web_streams
        )
        raise ConfigError(
            "Property has multiple web data streams. Pass --stream explicitly. "
            f"Available streams: {available}"
        )

    return web_streams[0].name


def get_enhanced_measurement_settings(
    property_id: str,
    credentials_path: Path | None = None,
    stream: str | None = None,
) -> EnhancedMeasurementSettings:
    stream_name = resolve_web_stream_name(property_id, credentials_path, stream)
    parsed = BetaAnalyticsAdminClient.parse_data_stream_path(stream_name)
    settings_name = AlphaAnalyticsAdminClient.enhanced_measurement_settings_path(
        parsed["property"],
        parsed["data_stream"],
    )
    client = build_alpha_admin_client(credentials_path, scopes=ADMIN_PROPERTY_READ_SCOPES)
    return client.get_enhanced_measurement_settings(name=settings_name)


def list_access_bindings(
    property_id: str,
    credentials_path: Path | None = None,
) -> list[AccessBinding]:
    client = build_alpha_admin_client(credentials_path, scopes=ADMIN_ACCESS_READ_SCOPES)
    pager = client.list_access_bindings(parent=property_resource_name(property_id))
    return list(pager)


def set_property_timezone(
    property_id: str,
    time_zone: str,
    credentials_path: Path | None = None,
    *,
    apply: bool = False,
) -> Property | TimezoneUpdatePreview:
    validated_time_zone = validate_timezone(time_zone)
    current_property = inspect_property(property_id, credentials_path)

    if not apply:
        return TimezoneUpdatePreview(
            property_name=current_property.name,
            current_time_zone=current_property.time_zone,
            requested_time_zone=validated_time_zone,
            apply=False,
        )

    client = build_beta_admin_client(credentials_path, scopes=ADMIN_PROPERTY_EDIT_SCOPES)
    property_name = property_resource_name(property_id)
    return client.update_property(
        property=Property(name=property_name, time_zone=validated_time_zone),
        update_mask=FieldMask(paths=["time_zone"]),
    )


def property_rows(property_resource: Property) -> list[list[str]]:
    property_dict = message_to_dict(property_resource)
    ordered_keys = [
        "name",
        "display_name",
        "parent",
        "account",
        "time_zone",
        "currency_code",
        "industry_category",
        "service_level",
        "property_type",
        "create_time",
        "update_time",
        "delete_time",
        "expire_time",
    ]
    return [
        [key, str(property_dict.get(key, ""))]
        for key in ordered_keys
        if property_dict.get(key, "") != ""
    ]


def stream_rows(streams: list[DataStream]) -> list[list[str]]:
    rows: list[list[str]] = []
    for stream in streams:
        stream_dict = message_to_dict(stream)
        web_stream_data = stream_dict.get("web_stream_data", {})
        rows.append(
            [
                str(stream_dict.get("name", "")),
                str(stream_dict.get("display_name", "")),
                str(stream_dict.get("type_", "")),
                str(web_stream_data.get("measurement_id", "")),
                str(web_stream_data.get("default_uri", "")),
                str(stream_dict.get("create_time", "")),
                str(stream_dict.get("update_time", "")),
            ]
        )
    return rows


def access_binding_rows(bindings: list[AccessBinding]) -> list[list[str]]:
    rows: list[list[str]] = []
    for binding in bindings:
        binding_dict = message_to_dict(binding)
        rows.append(
            [
                str(binding_dict.get("user", "")),
                ", ".join(binding_dict.get("roles", [])),
                str(binding_dict.get("name", "")),
            ]
        )
    return rows


def enhanced_measurement_rows(
    settings: EnhancedMeasurementSettings,
) -> list[list[str]]:
    settings_dict = message_to_dict(settings)
    ordered_keys = [
        "name",
        "stream_enabled",
        "page_changes_enabled",
        "scrolls_enabled",
        "outbound_clicks_enabled",
        "site_search_enabled",
        "video_engagement_enabled",
        "file_downloads_enabled",
        "form_interactions_enabled",
        "search_query_parameter",
        "uri_query_parameter",
    ]
    return [
        [key, str(settings_dict.get(key, ""))]
        for key in ordered_keys
        if key in settings_dict
    ]


def timezone_preview_rows(preview: TimezoneUpdatePreview) -> list[list[str]]:
    return [
        ["property_name", preview.property_name],
        ["current_time_zone", preview.current_time_zone],
        ["requested_time_zone", preview.requested_time_zone],
        ["apply", str(preview.apply)],
    ]
