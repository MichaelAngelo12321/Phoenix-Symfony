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
    gender_atom = String.to_existing_atom(gender)
    from u in query, where: u.gender == ^gender_atom
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
       when sort_field in ["id", "first_name", "last_name", "birthdate", "gender"] and 
            sort_order in ["asc", "desc"] do
    field = String.to_atom(sort_field)
    order = String.to_atom(sort_order)
    
    from u in query, order_by: [{^order, field(u, ^field)}]
  end
  defp apply_sorting(query, %{"sort_by" => sort_field}) 
       when sort_field in ["id", "first_name", "last_name", "birthdate", "gender"] do
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
  Generates random user data for testing purposes.
  """
  def generate_random_user do
    first_names_male = ["Adam", "Bartosz", "Cezary", "Damian", "Emil", "Filip", "Grzegorz", "Henryk", "Igor", "Jakub"]
    first_names_female = ["Anna", "Barbara", "Celina", "Dorota", "Ewa", "Franciszka", "Grażyna", "Halina", "Irena", "Joanna"]
    last_names = ["Nowak", "Kowalski", "Wiśniewski", "Dąbrowski", "Lewandowski", "Wójcik", "Kamiński", "Kowalczyk", "Zieliński", "Szymański"]
    
    gender = Enum.random(["male", "female"])
    first_name = case gender do
      "male" -> Enum.random(first_names_male)
      "female" -> Enum.random(first_names_female)
    end
    
    %{
      first_name: first_name,
      last_name: Enum.random(last_names),
      gender: gender,
      birthdate: Date.add(Date.utc_today(), -Enum.random(18..80) * 365)
    }
  end

  # Private functions to fetch data from dane.gov.pl API
  defp fetch_male_names do
    # Fetch from working dane.gov.pl API endpoint
    case HTTPoison.get("https://api.dane.gov.pl/1.4/resources/63929/data?per_page=100", [], timeout: 10000) do
      {:ok, %HTTPoison.Response{status_code: 200, body: body}} ->
        case Jason.decode(body) do
          {:ok, %{"data" => records}} when is_list(records) ->
            names = records
                   |> Enum.map(fn record -> 
                      get_in(record, ["attributes", "col1", "val"])
                   end)
                   |> Enum.filter(&is_binary/1)
                   |> Enum.take(100)
            {:ok, names}
          _ ->
            {:ok, get_fallback_male_names()}
        end
      _ ->
        {:ok, get_fallback_male_names()}
    end
  end

  defp get_fallback_male_names do
    ["Adam", "Piotr", "Krzysztof", "Stanisław", "Andrzej", "Tomasz", "Jan", "Paweł", "Michał", "Marcin",
     "Grzegorz", "Jerzy", "Tadeusz", "Łukasz", "Zbigniew", "Ryszard", "Kazimierz", "Marek", "Marian",
     "Henryk", "Dariusz", "Mariusz", "Józef", "Wojciech", "Robert", "Rafał", "Jacek", "Janusz", "Mirosław",
     "Maciej", "Sławomir", "Jarosław", "Kamil", "Wiesław", "Roman", "Władysław", "Leszek", "Bartosz", "Artur",
     "Daniel", "Sebastian", "Dawid", "Przemysław", "Filip", "Mateusz", "Hubert", "Dominik", "Adrian", "Konrad",
     "Patryk", "Jakub", "Marcin", "Damian", "Kacper", "Oskar", "Wiktor", "Szymon", "Maksymilian", "Natan"]
  end

  defp fetch_female_names do
    # Try to fetch from API, fallback to comprehensive list if fails
    case HTTPoison.get("https://api.dane.gov.pl/1.4/resources/63924/data?per_page=100", [], timeout: 5000) do
      {:ok, %HTTPoison.Response{status_code: 200, body: body}} ->
        case Jason.decode(body) do
          {:ok, %{"data" => data}} when is_list(data) ->
            names = data
            |> Enum.map(fn item -> 
              get_in(item, ["attributes", "col1", "val"])
            end)
            |> Enum.filter(&is_binary/1)
            {:ok, names}
          _ ->
            {:ok, get_fallback_female_names()}
        end
      _ ->
        {:ok, get_fallback_female_names()}
    end
  end

  defp get_fallback_female_names do
    ["Anna", "Maria", "Katarzyna", "Małgorzata", "Agnieszka", "Barbara", "Ewa", "Elżbieta", "Zofia", "Krystyna",
     "Irena", "Teresa", "Danuta", "Janina", "Stanisława", "Helena", "Halina", "Jadwiga", "Józefa", "Marianna",
     "Aleksandra", "Monika", "Beata", "Dorota", "Renata", "Grażyna", "Jolanta", "Bożena", "Urszula", "Iwona",
     "Magdalena", "Joanna", "Wanda", "Genowefa", "Stefania", "Alicja", "Justyna", "Sylwia", "Aneta", "Edyta",
     "Natalia", "Paulina", "Karolina", "Patrycja", "Ewelina", "Agata", "Marta", "Izabela", "Weronika", "Klaudia",
     "Julia", "Zuzanna", "Martyna", "Oliwia", "Maja", "Lena", "Amelia", "Hanna", "Gabriela", "Nikola"]
  end

  defp fetch_male_surnames do
    # Try to fetch from API, fallback to comprehensive list if fails
    case HTTPoison.get("https://api.dane.gov.pl/1.4/resources/63892/data?per_page=100", [], timeout: 5000) do
      {:ok, %HTTPoison.Response{status_code: 200, body: body}} ->
        case Jason.decode(body) do
          {:ok, %{"data" => data}} when is_list(data) ->
            surnames = data
            |> Enum.map(fn item -> 
              get_in(item, ["attributes", "col1", "val"])
            end)
            |> Enum.filter(&is_binary/1)
            {:ok, surnames}
          _ ->
            {:ok, get_fallback_male_surnames()}
        end
      _ ->
        {:ok, get_fallback_male_surnames()}
    end
  end

  defp get_fallback_male_surnames do
    ["Nowak", "Kowalski", "Wiśniewski", "Wójcik", "Kowalczyk", "Kamiński", "Lewandowski", "Zieliński", "Szymański", "Woźniak",
     "Dąbrowski", "Kozłowski", "Jankowski", "Mazur", "Wojciechowski", "Kwiatkowski", "Krawczyk", "Kaczmarek", "Piotrowski", "Grabowski",
     "Nowakowski", "Pawłowski", "Michalski", "Nowicki", "Adamczyk", "Dudek", "Zając", "Wieczorek", "Jabłoński", "Król",
     "Majewski", "Olszewski", "Jaworski", "Wróbel", "Malinowski", "Pawlak", "Witkowski", "Walczak", "Stępień", "Górski",
     "Rutkowski", "Michalak", "Sikora", "Ostrowski", "Baran", "Duda", "Szewczyk", "Tomaszewski", "Pietrzak", "Marciniak",
     "Wróblewski", "Zalewski", "Jakubowski", "Jasiński", "Zawadzki", "Sadowski", "Bąk", "Chmielewski", "Włodarczyk", "Borkowski"]
  end

  defp fetch_female_surnames do
    # Try to fetch from API, fallback to comprehensive list if fails
    case HTTPoison.get("https://api.dane.gov.pl/1.4/resources/63888/data?per_page=100", [], timeout: 5000) do
      {:ok, %HTTPoison.Response{status_code: 200, body: body}} ->
        case Jason.decode(body) do
          {:ok, %{"data" => data}} when is_list(data) ->
            surnames = data
            |> Enum.map(fn item -> 
              get_in(item, ["attributes", "col1", "val"])
            end)
            |> Enum.filter(&is_binary/1)
            {:ok, surnames}
          _ ->
            {:ok, get_fallback_female_surnames()}
        end
      _ ->
        {:ok, get_fallback_female_surnames()}
    end
  end

  defp get_fallback_female_surnames do
    ["Nowak", "Kowalska", "Wiśniewska", "Wójcik", "Kowalczyk", "Kamińska", "Lewandowska", "Zielińska", "Szymańska", "Woźniak",
     "Dąbrowska", "Kozłowska", "Jankowska", "Mazur", "Wojciechowska", "Kwiatkowska", "Krawczyk", "Kaczmarek", "Piotrowska", "Grabowska",
     "Nowakowska", "Pawłowska", "Michalska", "Nowicka", "Adamczyk", "Dudek", "Zając", "Wieczorek", "Jabłońska", "Król",
     "Majewska", "Olszewska", "Jaworska", "Wróbel", "Malinowska", "Pawlak", "Witkowska", "Walczak", "Stępień", "Górska",
     "Rutkowska", "Michalak", "Sikora", "Ostrowska", "Baran", "Duda", "Szewczyk", "Tomaszewska", "Pietrzak", "Marciniak",
     "Wróblewska", "Zalewska", "Jakubowska", "Jasińska", "Zawadzka", "Sadowska", "Bąk", "Chmielewska", "Włodarczyk", "Borkowska"]
  end

  @doc """
  Imports sample users data.
  Generates 100 random users with names from Polish PESEL registry.
  """
  def import_sample_users do
    # Fetch names and surnames from dane.gov.pl API
    {:ok, male_names} = fetch_male_names()
    {:ok, female_names} = fetch_female_names()
    {:ok, male_surnames} = fetch_male_surnames()
    {:ok, female_surnames} = fetch_female_surnames()
    
    # Generate 100 random users
    users_data = for _i <- 1..100 do
      gender = Enum.random([:male, :female])
      {first_name, last_name} = case gender do
        :male -> {Enum.random(male_names), Enum.random(male_surnames)}
        :female -> {Enum.random(female_names), Enum.random(female_surnames)}
      end
      
      # Random birthdate between 1970-01-01 and 2024-12-31
      start_date = ~D[1970-01-01]
      end_date = ~D[2024-12-31]
      days_diff = Date.diff(end_date, start_date)
      random_days = :rand.uniform(days_diff)
      birthdate = Date.add(start_date, random_days)
      
      %{
        first_name: first_name,
        last_name: last_name,
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
