defmodule PhoenixApi.Accounts.User do
  use Ecto.Schema
  import Ecto.Changeset

  schema "users" do
    field :first_name, :string
    field :last_name, :string
    field :birthdate, :date
    field :gender, Ecto.Enum, values: [:male, :female]

    timestamps(type: :utc_datetime)
  end

  @doc false
  def changeset(user, attrs) do
    user
    |> cast(attrs, [:first_name, :last_name, :birthdate, :gender])
    |> validate_required([:first_name, :last_name, :birthdate, :gender])
    |> validate_length(:first_name, min: 2, max: 50)
    |> validate_length(:last_name, min: 2, max: 50)
    |> validate_inclusion(:gender, [:male, :female])
    |> validate_birthdate()
  end

  defp validate_birthdate(changeset) do
    case get_field(changeset, :birthdate) do
      nil -> changeset
      birthdate ->
        today = Date.utc_today()
        cond do
          Date.compare(birthdate, today) == :gt ->
            add_error(changeset, :birthdate, "cannot be in the future")
          Date.diff(today, birthdate) > 36500 -> # ~100 years
            add_error(changeset, :birthdate, "cannot be more than 100 years ago")
          true -> changeset
        end
    end
  end
end
