defmodule PhoenixApi.AuthHelpers do
  @moduledoc """
  Authentication helpers for tests
  """

  import Plug.Conn
  alias PhoenixApi.Guardian

  @doc """
  Creates a JWT token for testing purposes
  """
  def create_jwt_token(email \\ "admin@example.com") do
    # Create a user struct that matches Guardian expectations
    user = %{email: email}
    case Guardian.encode_and_sign(user) do
      {:ok, token, _claims} -> token
      {:error, _reason} -> "mock_jwt_token_for_testing"
    end
  end

  @doc """
  Adds JWT authorization header to connection
  """
  def authenticate_conn(conn, email \\ "admin@example.com") do
    token = create_jwt_token(email)
    put_req_header(conn, "authorization", "Bearer #{token}")
  end
end