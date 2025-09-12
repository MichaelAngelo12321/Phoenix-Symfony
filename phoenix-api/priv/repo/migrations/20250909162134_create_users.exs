defmodule PhoenixApi.Repo.Migrations.CreateUsers do
  use Ecto.Migration

  def change do
    execute "CREATE TYPE gender_enum AS ENUM ('male', 'female')", "DROP TYPE gender_enum"
    
    create table(:users) do
      add :first_name, :string, null: false, size: 50, collate: "pl-PL-x-icu"
      add :last_name, :string, null: false, size: 50, collate: "pl-PL-x-icu"
      add :birthdate, :date, null: false
      add :gender, :gender_enum, null: false

      timestamps(type: :utc_datetime)
    end

    create index(:users, [:last_name, :first_name])
  end
end
