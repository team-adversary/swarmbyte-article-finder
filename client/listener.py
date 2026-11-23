from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from urllib.parse import parse_qs, urlsplit


CLIENT_DIRECTORY = Path(__file__).resolve().parent
MAX_FILE_SIZE = 1024 * 1024


def read_file(path: str) -> bytes:
    candidate = (CLIENT_DIRECTORY / path).resolve()
    if not candidate.is_relative_to(CLIENT_DIRECTORY) or not candidate.is_file():
        raise FileNotFoundError(path)

    with candidate.open("rb") as source:
        content = source.read(MAX_FILE_SIZE + 1)
    if len(content) > MAX_FILE_SIZE:
        raise ValueError("file is too large")
    return content


class ArticleHandler(BaseHTTPRequestHandler):
    server_version = "SwarmByteArticleClient/2.1.2"
    sys_version = ""

    def do_GET(self) -> None:
        request = urlsplit(self.path)
        if request.path == "/health":
            self.send_content(200, b"ok\n")
            return
        if request.path != "/article":
            self.send_error(404)
            return

        paths = parse_qs(request.query, keep_blank_values=True).get("path", [])
        if len(paths) != 1 or not paths[0]:
            self.send_error(400)
            return

        try:
            content = read_file(paths[0])
        except (FileNotFoundError, PermissionError, OSError):
            self.send_error(404)
            return
        except ValueError:
            self.send_error(413)
            return

        self.send_content(200, content)

    def send_content(self, status: int, content: bytes) -> None:
        self.send_response(status)
        self.send_header("Content-Type", "text/plain; charset=utf-8")
        self.send_header("Content-Length", str(len(content)))
        self.end_headers()
        self.wfile.write(content)


if __name__ == "__main__":
    ThreadingHTTPServer(("0.0.0.0", 8080), ArticleHandler).serve_forever()
