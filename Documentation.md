# Running Documentation Locally

The documentation is built with [Sphinx](https://www.sphinx-doc.org/) and uses the `furo` theme. It is published at [https://llphant.readthedocs.org](https://llphant.readthedocs.org).

## Prerequisites

Python 3.11 is recommended (as specified in `docs/.readthedocs.yaml`).

## Setup

```bash
# 1. Create a virtual environment (from repo root)
python3 -m venv docs/.venv

# 2. Activate it
source docs/.venv/bin/activate   # Linux/macOS
# docs\.venv\Scripts\activate    # Windows

# 3. Install dependencies
pip install sphinx -r docs/requirements.txt
```

## Build and view

```bash
# Build the docs
sphinx-build -b html docs docs/_build/html

# Open in browser
xdg-open docs/_build/html/index.html  # Linux
open docs/_build/html/index.html      # macOS
```

## Live reload (optional)

```bash
pip install sphinx-autobuild
sphinx-autobuild docs docs/_build/html
# Visit http://127.0.0.1:8000
```

To use a different port, pass the `--port` flag:

```bash
sphinx-autobuild docs docs/_build/html --port 9123
# Visit http://127.0.0.1:9123
```

## Deactivate the virtual environment

```bash
deactivate
```

> **Note:** `docs/.venv` and `docs/_build` should be added to `.gitignore` if not already present.
