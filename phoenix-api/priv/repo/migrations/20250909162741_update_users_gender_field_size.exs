defmodule PhoenixApi.Repo.Migrations.UpdateUsersGenderFieldSize do
  use Ecto.Migration

  def change do
    # Drop the old constraint first
    drop constraint(:users, :gender_check)
    
    # Alter the gender column to increase size
    alter table(:users) do
      modify :gender, :string, size: 10, null: false
    end
    
    # Recreate the constraint with new values
    create constraint(:users, :gender_check, check: "gender IN ('male', 'female')")
  end
end
