defmodule PhoenixApiWeb.UserControllerTest do
  use PhoenixApiWeb.ConnCase

  import PhoenixApi.AccountsFixtures
  import PhoenixApi.AuthHelpers
  alias PhoenixApi.Accounts.User

  @create_attrs %{
    first_name: "some first_name",
    last_name: "some last_name",
    birthdate: ~D[2025-09-08],
    gender: "male"
  }
  @update_attrs %{
    first_name: "some updated first_name",
    last_name: "some updated last_name",
    birthdate: ~D[2025-09-09],
    gender: "female"
  }
  @invalid_attrs %{first_name: nil, last_name: nil, birthdate: nil, gender: nil}

  setup %{conn: conn} do
    conn = 
      conn
      |> put_req_header("accept", "application/json")
      |> authenticate_conn()
    {:ok, conn: conn}
  end

  describe "index" do
    test "lists all users", %{conn: conn} do
      conn = get(conn, ~p"/api/users")
      assert json_response(conn, 200)["data"] == []
    end
  end

  describe "create user" do
    test "renders user when data is valid", %{conn: conn} do
      conn = post(conn, ~p"/api/users", user: @create_attrs)
      assert %{"id" => id} = json_response(conn, 201)["data"]

      conn = get(conn, ~p"/api/users/#{id}")

      assert %{
               "id" => ^id,
               "birthdate" => "2025-09-08",
               "first_name" => "some first_name",
               "gender" => "male",
               "last_name" => "some last_name"
             } = json_response(conn, 200)["data"]
    end

    test "renders errors when data is invalid", %{conn: conn} do
      conn = post(conn, ~p"/api/users", user: @invalid_attrs)
      assert json_response(conn, 422)["errors"] != %{}
    end
  end

  describe "update user" do
    setup [:create_user]

    test "renders user when data is valid", %{conn: conn, user: %User{id: id} = user} do
      conn = put(conn, ~p"/api/users/#{user}", user: @update_attrs)
      assert %{"id" => ^id} = json_response(conn, 200)["data"]

      conn = get(conn, ~p"/api/users/#{id}")

      assert %{
               "id" => ^id,
               "birthdate" => "2025-09-09",
               "first_name" => "some updated first_name",
               "gender" => "female",
               "last_name" => "some updated last_name"
             } = json_response(conn, 200)["data"]
    end

    test "renders errors when data is invalid", %{conn: conn, user: user} do
      conn = put(conn, ~p"/api/users/#{user}", user: @invalid_attrs)
      assert json_response(conn, 422)["errors"] != %{}
    end
  end

  describe "delete user" do
    setup [:create_user]

    test "deletes chosen user", %{conn: conn, user: user} do
      conn = delete(conn, ~p"/api/users/#{user}")
      assert response(conn, 204)

      assert_error_sent 404, fn ->
        get(conn, ~p"/api/users/#{user}")
      end
    end
  end

  describe "import users" do
    test "requires authentication", %{conn: conn} do
      # Remove authentication header
      conn = 
        conn
        |> delete_req_header("authorization")
        |> post(~p"/api/import")
      
      response = json_response(conn, 401)
      assert response["success"] == false
      assert response["error"] =~ "Unauthorized"
    end
  end

  defp create_user(_) do
    user = user_fixture()

    %{user: user}
  end
end
