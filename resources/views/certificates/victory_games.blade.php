<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    @page { size: 297mm 210mm; margin: 0; }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'DejaVu Serif', Georgia, serif;
        background: #fff;
        color: #1a1a2e;
    }

    .page {
        position: absolute;
        top: 0; left: 0;
        width: 297mm; height: 210mm;
        background: #fff;
    }

    /* Borders - explicit sides so dompdf renders them fully */
    .b-outer {
        position: absolute;
        top: 7mm; right: 7mm; bottom: 7mm; left: 7mm;
        border: 3px solid #c9a84c;
    }
    .b-inner {
        position: absolute;
        top: 11mm; right: 11mm; bottom: 11mm; left: 11mm;
        border: 1px solid #c9a84c;
    }

    /* Dark header band inside the inner border */
    .header-band {
        position: absolute;
        top: 11mm; left: 11mm; right: 11mm;
        background: #1a1a2e;
        color: #c9a84c;
        text-align: center;
        padding: 5px 0;
        letter-spacing: 5px;
        font-size: 7.5pt;
        text-transform: uppercase;
        font-family: 'DejaVu Sans', sans-serif;
    }

    /* Main content centered vertically via table */
    .content-wrap {
        position: absolute;
        top: 26mm; left: 12mm; right: 12mm; bottom: 14mm;
        display: table;
        width: 273mm;
    }
    .content-cell {
        display: table-cell;
        vertical-align: middle;
        text-align: center;
        padding: 0 18mm;
    }

    .placement-label {
        display: inline-block;
        border: 1.5px solid #c9a84c;
        color: #c9a84c;
        letter-spacing: 5px;
        font-size: 8pt;
        text-transform: uppercase;
        padding: 4px 20px;
        font-family: 'DejaVu Sans', sans-serif;
        margin-bottom: 6mm;
    }

    .cert-title {
        font-size: 10pt;
        letter-spacing: 4px;
        text-transform: uppercase;
        color: #aaa;
        margin-bottom: 2mm;
        font-family: 'DejaVu Sans', sans-serif;
    }

    .cert-type {
        font-size: 34pt;
        font-weight: bold;
        color: #c9a84c;
        margin-bottom: 6mm;
    }

    .awarded-to {
        font-size: 9pt;
        letter-spacing: 4px;
        text-transform: uppercase;
        color: #aaa;
        margin-bottom: 2mm;
        font-family: 'DejaVu Sans', sans-serif;
    }

    .recipient-name {
        font-size: 30pt;
        font-style: italic;
        color: #1a1a2e;
        border-bottom: 2px solid #c9a84c;
        padding-bottom: 2mm;
        margin-bottom: 6mm;
    }

    .body-text {
        font-size: 10pt;
        color: #555;
        line-height: 1.7;
        margin-bottom: 0;
    }

    .comp-name {
        font-weight: bold;
        font-style: italic;
        color: #1a1a2e;
    }

    /* Footer pinned to bottom inside border */
    .footer {
        position: absolute;
        bottom: 12mm; left: 0; right: 0;
        text-align: center;
        font-size: 6.5pt;
        color: #bbb;
        letter-spacing: 1px;
        font-family: 'DejaVu Sans', sans-serif;
    }
</style>
</head>
<body>
<div class="page">
    <div class="b-outer"></div>
    <div class="b-inner"></div>

    <div class="header-band">VibeCode Victory Games &nbsp;&middot;&nbsp; Official Certificate</div>

    <div class="content-wrap">
        <div class="content-cell">

            <div class="placement-label">
                @if($cert->certificate_type === '1st') &mdash;&nbsp; Gold &nbsp;&mdash;
                @elseif($cert->certificate_type === '2nd') &mdash;&nbsp; Silver &nbsp;&mdash;
                @elseif($cert->certificate_type === '3rd') &mdash;&nbsp; Bronze &nbsp;&mdash;
                @else &mdash;&nbsp; Participant &nbsp;&mdash;
                @endif
            </div>

            <div class="cert-title">Certificate of</div>
            <div class="cert-type">{{ $type_label }}</div>

            <div class="awarded-to">Awarded to</div>
            <div class="recipient-name">{{ $victor->display_name }}</div>

            <div class="body-text">
                @if($cert->certificate_type === 'participation')
                    for participating in the
                @else
                    for achieving <strong>{{ $type_label }}</strong> in the
                @endif
                <span class="comp-name">{{ $competition->name }}</span>@if($competition->held_at)
                    &nbsp;&mdash;&nbsp; held {{ $competition->held_at->format('F j, Y') }}@endif<br>
                an AI-judged UX testing competition where apps compete head-to-head
                in single-elimination brackets evaluated by large language models.
            </div>

        </div>
    </div>

    <div class="footer">
        VibeCode Victory Games &nbsp;&middot;&nbsp; Issued {{ $cert->issued_at->format('F j, Y') }}
        &nbsp;&middot;&nbsp; Token: {{ $cert->download_token }}
    </div>
</div>
</body>
</html>
