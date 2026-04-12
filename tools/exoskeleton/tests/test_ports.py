import socket

from project_exoskeleton.ports import find_open_port, is_port_available


def test_find_open_port_returns_bindable_port() -> None:
    port = find_open_port("127.0.0.1")

    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as sock:
        sock.bind(("127.0.0.1", port))


def test_is_port_available_detects_busy_port() -> None:
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as sock:
        sock.bind(("127.0.0.1", 0))
        sock.listen()
        port = int(sock.getsockname()[1])
        assert is_port_available("127.0.0.1", port) is False
