import sys, os
from sphinx.highlighting import lexers
from pygments.lexers.web import PhpLexer

lexers['php'] = PhpLexer(startinline=True, linenos=0)
lexers['php-annotations'] = PhpLexer(startinline=True, linenos=0)
primary_domain = 'php'

extensions = []
templates_path = ['_templates']
source_suffix = '.rst'
master_doc = 'index'
project = u'LLPhant'
copyright = u'MIT License'
version = '9'
html_title = "LLPhant - The PHP library for Gen AI and Vector DBs"
html_short_title = "LLPhant — PHP library for Gen AI and Vector DBs"
html_favicon = 'assets/favicon.ico'

exclude_patterns = ['_build']
html_static_path = ['_static']
html_css_files = [
    'styles.css',
]

##### Furo theme

html_theme = 'furo'

html_theme_options = {
    "light_css_variables": {
        "color-brand-primary": "#2980b9",
        "color-brand-content": "#2980b9",
    },
    "dark_css_variables": {
        "color-brand-primary": "#4db3ff",
        "color-brand-content": "#4db3ff",
    },
}
