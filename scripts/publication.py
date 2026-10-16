"""Prüft öffentliche Dateien und erzeugt das Wiki ausschließlich aus docs/."""

import json
import re
from pathlib import Path, PurePosixPath
from urllib.parse import urlsplit

SECRET_RULES = {
    "Privater Schlüssel": rb"-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----",
    "API-Token": (
        rb"\b(?:sk-(?:proj-)?[A-Za-z0-9_-]{24,}|"
        rb"gh[pousr]_[A-Za-z0-9_]{24,}|github_pat_[A-Za-z0-9_]{30,})"
    ),
    "Zugangsdaten in URL": rb"https?://[^\s/:]+:[^\s/@]+@",
    "Gespeichertes Secret": (
        rb"(?im)(?:api[_-]?key|password|access[_-]?token|secret)"
        rb"\s*[=:]\s*[\x22\x27][A-Za-z0-9/+_-]{16,}[\x22\x27]"
    ),
}
LINK = re.compile(r"(!?)\[([^\]]*)\]\(([^\s)]+)\)")
MANIFEST = ".docs-generated-files.json"


def check_files(files, forbidden=()):
    """Prüft Inhalte ohne gefundene Werte in Fehlermeldungen offenzulegen."""
    errors = []
    rules = dict(SECRET_RULES)
    rules.update(
        {f"Lokale Ausschlussregel {i + 1}": p.encode() for i, p in enumerate(forbidden)}
    )
    for name, data in files.items():
        path = PurePosixPath(name)
        if path.is_absolute() or ".." in path.parts:
            raise ValueError("Unsicherer Veröffentlichungspfad.")
        if path.parts[0] in {"vendor", "node_modules"} or any(
            part in {".git", ".vscode", ".idea"} for part in path.parts
        ):
            errors.append(f"{name}: lokale Dateien")
        if (
            path.name.startswith(".env")
            or path.suffix in {".token", ".key", ".pem", ".log"}
            or path.name == "auth.json"
        ):
            errors.append(f"{name}: geschützte Dateiklasse")
        for label, pattern in rules.items():
            if re.search(pattern, data):
                errors.append(f"{name}: {label}")
    if errors:
        raise ValueError("Veröffentlichungsprüfung abgebrochen:\n" + "\n".join(errors))


def wiki_pages(files, repository):
    """Übersetzt lokale Markdown-Verweise und Bilder für GitHub-Wikiseiten."""
    prefix = "docs/"
    docs = {
        name.removeprefix(prefix): data
        for name, data in files.items()
        if name.startswith(prefix)
    }
    pages = {
        name: ("Home" if name == "index.md" else name[:-3])
        for name in docs
        if name.endswith(".md")
    }
    if "index.md" not in pages:
        raise ValueError("docs/index.md fehlt.")
    result = {}
    wiki_url = repository.removesuffix(".git") + "/wiki"
    owner_repo = urlsplit(repository).path.strip("/").removesuffix(".git")

    def replace_link(match):
        """Erhält externe Links und prüft alle lokalen Dokumentationsziele."""
        image, label, target = match.groups()
        url = urlsplit(target)
        if url.scheme or url.netloc or not url.path:
            return match.group(0)
        if url.path.startswith("/") or ".." in PurePosixPath(url.path).parts:
            raise ValueError("Nicht unterstützter relativer Wiki-Verweis: " + target)
        if url.path not in docs:
            raise ValueError("Fehlendes Wiki-Ziel: " + target)
        suffix = ("#" + url.fragment) if url.fragment else ""
        if url.path in pages:
            converted = wiki_url + "/" + pages[url.path] + suffix
        else:
            converted = (
                "https://raw.githubusercontent.com/wiki/" + owner_repo + "/" + url.path
            )
        return f"{image}[{label}]({converted})"

    for name, data in docs.items():
        if name in pages:
            result[pages[name] + ".md"] = LINK.sub(replace_link, data.decode()).encode()
        else:
            result[name] = data
    sidebar = ["## Anleitung", ""]
    for line in files["mkdocs.yml"].decode().splitlines():
        item = re.match(r"\s*- ([^:]+):(?:\s+([^\s]+\.md))?\s*$", line)
        if not item:
            continue
        title, path = item.groups()
        if path:
            if path not in pages:
                raise ValueError("Navigationsziel fehlt: " + path)
            sidebar.append(f"- [{title}]({wiki_url}/{pages[path]})")
        else:
            sidebar.extend(["", f"**{title}**", ""])
    result["_Sidebar.md"] = ("\n".join(sidebar) + "\n").encode()
    result["_Footer.md"] = (
        (
            "Diese Anleitung wird aus [docs/]([URL]/tree/main/docs) erzeugt. "
            "Änderungen bitte dort pflegen.\n"
        )
        .replace("[URL]", repository.removesuffix(".git"))
        .encode()
    )
    result[MANIFEST] = (json.dumps(sorted(result), indent=2) + "\n").encode()
    return result


def write_managed(root, files, old_names):
    """Ersetzt nur explizit verwaltete Dateien innerhalb des sauberen Checkouts."""
    root = Path(root).resolve()
    for name in set(old_names) | set(files):
        target = root / name
        if not target.resolve().is_relative_to(root) or ".git" in Path(name).parts:
            raise ValueError("Unsicheres Dateiziel.")
        if name in files:
            target.parent.mkdir(parents=True, exist_ok=True)
            target.write_bytes(files[name])
        elif target.is_file():
            target.unlink()
