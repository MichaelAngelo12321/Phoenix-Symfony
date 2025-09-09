defmodule PhoenixApi.Repo.Migrations.CreateUsers do
  use Ecto.Migration

  def change do
    create table(:users) do
      add :first_name, :string, null: false, size: 50
      add :last_name, :string, null: false, size: 50
      add :birthdate, :date, null: false
      add :gender, :string, null: false, size: 1

      timestamps(type: :utc_datetime)
    end

    create index(:users, [:last_name, :first_name])
    create constraint(:users, :gender_check, check: "gender IN ('male', 'female')")
  end
end
