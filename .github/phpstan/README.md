# Optional PHPStan dependencies

The [Dependabot configuration](../dependabot.yml) includes this directory in
the same monthly Composer schedule as the application. When reviewing dependency
updates, run `composer audit --locked` from both the repository root and this
directory to check both lockfiles.
