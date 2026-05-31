# Security Policy

Please report security issues privately instead of opening a public issue with exploit details.

Report vulnerabilities via [GitHub private security advisories](https://github.com/vanobospace/zertify-oss/security/advisories/new). We aim to acknowledge reports within a few days.

Do not include secrets, API keys, service account JSON, production database dumps, private audio files, or third-party copyrighted exam material in reports or pull requests.

## Local secret handling

- Keep `.env` local.
- Keep Google Cloud service account files outside git-tracked paths.
- Rotate any API key that may have been pasted into a public issue, log, or commit.
