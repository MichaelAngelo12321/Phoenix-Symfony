defmodule PhoenixApiWeb.Plugs.AuthPlug do
  import Plug.Conn
  import Phoenix.Controller
  
  alias PhoenixApi.Guardian

  def init(opts), do: opts

  def call(conn, _opts) do
    case get_req_header(conn, "authorization") do
      ["Bearer " <> token] ->
        verify_token(conn, token)
      _ ->
        unauthorized(conn)
    end
  end

  defp verify_token(conn, token) do
    case Guardian.decode_and_verify(token) do
      {:ok, claims} ->
        case Guardian.resource_from_claims(claims) do
          {:ok, admin} ->
            conn
            |> assign(:current_admin, admin)
            |> assign(:authenticated, true)
            
          {:error, _reason} ->
            unauthorized(conn)
        end
        
      {:error, _reason} ->
        unauthorized(conn)
    end
  end

  defp unauthorized(conn) do
    conn
    |> put_status(:unauthorized)
    |> json(%{
      success: false,
      error: "Unauthorized. Valid JWT token required."
    })
    |> halt()
  end
end