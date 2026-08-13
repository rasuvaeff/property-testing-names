# Examples

Run them from the package root after `composer install`:

```bash
php examples/basic-usage.php
php examples/person-and-display-forms.php
```

Or inside Docker, when there is no PHP on the host:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 php examples/basic-usage.php
```

| Script | Shows | Needs server? |
|---|---|---|
| `basic-usage.php` | `Names::first()` / `last()` / `middle()` / `full()`, composing a name arbitrary with `Gen::tuple()` and `Gen::map()`, and the error for an unknown locale | No |
| `person-and-display-forms.php` | `Names::person()` with a patronymic, the display forms on `PersonName`, gender-consistent inflection in `ru`, and the error when a locale has no middle-name dataset | No |

Both scripts draw values through `Gen::sample($arbitrary, $count, $seed)` with
fixed seeds, so their output is reproducible; a property test never calls
`sample()` — it declares the arbitrary in a generators method and lets the
runner draw.
