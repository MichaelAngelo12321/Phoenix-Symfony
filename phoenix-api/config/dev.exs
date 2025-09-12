import Config


config :phoenix_api, PhoenixApi.Repo,
  url: System.get_env("DATABASE_URL") || "ecto://postgres:postgres@localhost/phoenix_symfony_dev",
  stacktrace: true,
  show_sensitive_data_on_connection_error: true,
  pool_size: 10

config :phoenix_api, PhoenixApiWeb.Endpoint,
  http: [ip: {0, 0, 0, 0}, port: String.to_integer(System.get_env("PORT") || "4000")],
  check_origin: false,
  code_reloader: true,
  debug_errors: true,
  secret_key_base: "O7xFGC2HswKQqB9Q+4b2xfnRGq1+L8B61jQUxilIyQutUuCq4jO+iDmsNurNRb/K",
  watchers: [
    esbuild: {Esbuild, :install_and_run, [:phoenix_api, ~w(--sourcemap=inline --watch)]},
    tailwind: {Tailwind, :install_and_run, [:phoenix_api, ~w(--watch)]}
  ]


config :phoenix_api, PhoenixApi.Guardian,
  issuer: "phoenix_api",
  secret_key: "dev-secret-key-change-in-production-very-long-and-secure-key-here"


config :phoenix_api, PhoenixApiWeb.Endpoint,
  live_reload: [
    web_console_logger: true,
    patterns: [
      ~r"priv/static/(?!uploads/).*(js|css|png|jpeg|jpg|gif|svg)$",
      ~r"priv/gettext/.*(po)$",
      ~r"lib/phoenix_api_web/(?:controllers|live|components|router)/?.*\.(ex|heex)$"
    ]
  ]


config :phoenix_api, dev_routes: true


config :logger, :default_formatter, format: "[$level] $message\n"


config :phoenix, :stacktrace_depth, 20


config :phoenix, :plug_init_mode, :runtime

config :phoenix_live_view,
  
  debug_heex_annotations: true,
  debug_attributes: true,
  
  enable_expensive_runtime_checks: true


config :swoosh, :api_client, false
