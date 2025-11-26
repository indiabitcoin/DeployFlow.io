{{ Illuminate\Mail\Markdown::parse('---') }}

Thank you,<br>
{{ config('app.name') ?? 'DeployFlow' }}

{{ Illuminate\Mail\Markdown::parse('[Contact Support](https://deployflow.io/docs/contact)') }}
