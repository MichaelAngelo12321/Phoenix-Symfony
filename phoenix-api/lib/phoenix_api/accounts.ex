defmodule PhoenixApi.Accounts do
  @moduledoc """
  The Accounts context.
  """

  import Ecto.Query, warn: false
  alias PhoenixApi.Repo

  alias PhoenixApi.Accounts.User

  @doc """
  Returns the list of users.

  ## Examples

      iex> list_users()
      [%User{}, ...]

  """
  def list_users(params \\ %{}) do
    User
    |> apply_filters(params)
    |> apply_sorting(params)
    |> Repo.all()
  end

  defp apply_filters(query, params) do
    query
    |> filter_by_first_name(params)
    |> filter_by_last_name(params)
    |> filter_by_gender(params)
    |> filter_by_birthdate_range(params)
  end

  defp filter_by_first_name(query, %{"first_name" => first_name}) when is_binary(first_name) and first_name != "" do
    from u in query, where: ilike(u.first_name, ^("%#{first_name}%"))
  end
  defp filter_by_first_name(query, _), do: query

  defp filter_by_last_name(query, %{"last_name" => last_name}) when is_binary(last_name) and last_name != "" do
    from u in query, where: ilike(u.last_name, ^("%#{last_name}%"))
  end
  defp filter_by_last_name(query, _), do: query

  defp filter_by_gender(query, %{"gender" => gender}) when gender in ["male", "female"] do
    from u in query, where: u.gender == ^gender
  end
  defp filter_by_gender(query, _), do: query

  defp filter_by_birthdate_range(query, %{"birthdate_from" => from_date, "birthdate_to" => to_date}) 
       when is_binary(from_date) and is_binary(to_date) do
    with {:ok, from_date} <- Date.from_iso8601(from_date),
         {:ok, to_date} <- Date.from_iso8601(to_date) do
      from u in query, where: u.birthdate >= ^from_date and u.birthdate <= ^to_date
    else
      _ -> query
    end
  end
  defp filter_by_birthdate_range(query, %{"birthdate_from" => from_date}) when is_binary(from_date) do
    with {:ok, from_date} <- Date.from_iso8601(from_date) do
      from u in query, where: u.birthdate >= ^from_date
    else
      _ -> query
    end
  end
  defp filter_by_birthdate_range(query, %{"birthdate_to" => to_date}) when is_binary(to_date) do
    with {:ok, to_date} <- Date.from_iso8601(to_date) do
      from u in query, where: u.birthdate <= ^to_date
    else
      _ -> query
    end
  end
  defp filter_by_birthdate_range(query, _), do: query

  defp apply_sorting(query, %{"sort_by" => sort_field, "sort_order" => sort_order}) 
       when sort_field in ["first_name", "last_name", "birthdate", "gender"] and 
            sort_order in ["asc", "desc"] do
    field = String.to_atom(sort_field)
    order = String.to_atom(sort_order)
    from u in query, order_by: [{^order, field(u, ^field)}]
  end
  defp apply_sorting(query, %{"sort_by" => sort_field}) 
       when sort_field in ["first_name", "last_name", "birthdate", "gender"] do
    field = String.to_atom(sort_field)
    from u in query, order_by: [asc: field(u, ^field)]
  end
  defp apply_sorting(query, _), do: from(u in query, order_by: [asc: u.id])

  @doc """
  Gets a single user.

  Raises `Ecto.NoResultsError` if the User does not exist.

  ## Examples

      iex> get_user!(123)
      %User{}

      iex> get_user!(456)
      ** (Ecto.NoResultsError)

  """
  def get_user!(id), do: Repo.get!(User, id)

  @doc """
  Creates a user.

  ## Examples

      iex> create_user(%{field: value})
      {:ok, %User{}}

      iex> create_user(%{field: bad_value})
      {:error, %Ecto.Changeset{}}

  """
  def create_user(attrs) do
    %User{}
    |> User.changeset(attrs)
    |> Repo.insert()
  end

  @doc """
  Updates a user.

  ## Examples

      iex> update_user(user, %{field: new_value})
      {:ok, %User{}}

      iex> update_user(user, %{field: bad_value})
      {:error, %Ecto.Changeset{}}

  """
  def update_user(%User{} = user, attrs) do
    user
    |> User.changeset(attrs)
    |> Repo.update()
  end

  @doc """
  Deletes a user.

  ## Examples

      iex> delete_user(user)
      {:ok, %User{}}

      iex> delete_user(user)
      {:error, %Ecto.Changeset{}}

  """
  def delete_user(%User{} = user) do
    Repo.delete(user)
  end

  @doc """
  Returns an `%Ecto.Changeset{}` for tracking user changes.

  ## Examples

      iex> change_user(user)
      %Ecto.Changeset{data: %User{}}

  """
  def change_user(%User{} = user, attrs \\ %{}) do
    User.changeset(user, attrs)
  end

  @doc """
  Imports sample users data.
  Generates 1000 random users with popular Polish names.
  """
  def import_sample_users do
    # Popular Polish first names
    male_names = [
      "Adam", "Piotr", "Krzysztof", "Stanisław", "Andrzej", "Tomasz", "Jan", "Paweł", "Michał", "Marcin",
      "Grzegorz", "Jerzy", "Tadeusz", "Adam", "Łukasz", "Zbigniew", "Ryszard", "Kazimierz", "Marek", "Marian",
      "Henryk", "Dariusz", "Mariusz", "Józef", "Wojciech", "Robert", "Rafał", "Jacek", "Janusz", "Mirosław",
      "Maciej", "Sławomir", "Jarosław", "Kamil", "Wiesław", "Roman", "Władysław", "Leszek", "Bartosz", "Artur",
      "Daniel", "Sebastian", "Dawid", "Przemysław", "Filip", "Mateusz", "Hubert", "Dominik", "Adrian", "Konrad"
    ]
    
    female_names = [
      "Anna", "Maria", "Katarzyna", "Małgorzata", "Agnieszka", "Krystyna", "Barbara", "Ewa", "Elżbieta", "Zofia",
      "Janina", "Teresa", "Magdalena", "Monika", "Jadwiga", "Danuta", "Irena", "Halina", "Helena", "Beata",
      "Aleksandra", "Marta", "Dorota", "Marianna", "Grażyna", "Jolanta", "Stanisława", "Iwona", "Karolina", "Bożena",
      "Urszula", "Justyna", "Renata", "Alicja", "Paulina", "Sylwia", "Natalia", "Wanda", "Joanna", "Edyta",
      "Patrycja", "Agata", "Aneta", "Izabela", "Ewelina", "Kinga", "Wioletta", "Kamila", "Milena", "Gabriela"
    ]
    
    # Popular Polish surnames
    surnames = [
      "Nowak", "Kowalski", "Wiśniewski", "Dąbrowski", "Lewandowski", "Wójcik", "Kamiński", "Kowalczyk", "Zieliński", "Szymański",
      "Woźniak", "Kozłowski", "Jankowski", "Wojciechowski", "Kwiatkowski", "Kaczmarek", "Mazur", "Krawczyk", "Piotrowski", "Grabowski",
      "Nowakowski", "Pawłowski", "Michalski", "Nowicki", "Adamczyk", "Dudek", "Zając", "Wieczorek", "Jabłoński", "Król",
      "Majewski", "Olszewski", "Jaworski", "Wróbel", "Malinowski", "Pawlak", "Witkowski", "Walczak", "Stępień", "Górski",
      "Rutkowski", "Michalak", "Sikora", "Ostrowski", "Baran", "Duda", "Szewczyk", "Tomaszewski", "Pietrzak", "Marciniak"
    ]
    
    # Generate 1000 random users
    users_data = for _i <- 1..1000 do
      gender = Enum.random(["male", "female"])
      first_name = case gender do
        "male" -> Enum.random(male_names)
        "female" -> Enum.random(female_names)
      end
      
      # Random birthdate between 1970-01-01 and 2024-12-31
      start_date = ~D[1970-01-01]
      end_date = ~D[2024-12-31]
      days_diff = Date.diff(end_date, start_date)
      random_days = :rand.uniform(days_diff)
      birthdate = Date.add(start_date, random_days)
      
      %{
        first_name: first_name,
        last_name: Enum.random(surnames),
        birthdate: birthdate,
        gender: gender
      }
    end
    
    # Insert users in batches for better performance
    now = DateTime.utc_now() |> DateTime.truncate(:second)
    users_with_timestamps = Enum.map(users_data, fn user ->
      user
      |> Map.put(:inserted_at, now)
      |> Map.put(:updated_at, now)
    end)
    
    {count, _} = Repo.insert_all(User, users_with_timestamps)
    {:ok, count}
  end
end
