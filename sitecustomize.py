"""Temporary secure redirect guard for the Titan Hub donor import.

Python imports ``sitecustomize`` automatically. During the one-time import this
prevents GitHub's bearer token from being forwarded to the Azure signed artifact
URL after a cross-host redirect. The file is deleted after the donor import.
"""
from __future__ import annotations

from urllib.parse import urlsplit
import urllib.request

_original_redirect_request = urllib.request.HTTPRedirectHandler.redirect_request


def _secure_redirect_request(self, req, fp, code, msg, headers, newurl):
    redirected = _original_redirect_request(self, req, fp, code, msg, headers, newurl)
    if redirected is not None:
        old_host = urlsplit(req.full_url).netloc.casefold()
        new_host = urlsplit(newurl).netloc.casefold()
        if old_host and new_host and old_host != new_host:
            redirected.remove_header("Authorization")
            redirected.remove_header("authorization")
    return redirected


urllib.request.HTTPRedirectHandler.redirect_request = _secure_redirect_request
urllib.request._opener = None
