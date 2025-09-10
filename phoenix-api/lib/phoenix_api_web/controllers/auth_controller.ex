defmodule PhoenixApiWeb.AuthController do
  use PhoenixApiWeb, :controller
  
  alias PhoenixApi.Guardian

  def login(conn, %{"email" => email, "password" => password}) do
    case Guardian.authenticate(email, password) do
      {:ok, admin} ->
        {:ok, token, _claims} = Guardian.encode_and_sign(admin)
        
        conn
        |> put_status(:ok)
        |> json(%{
          success: true,
          token: token,
          admin: %{
            email: admin.email,
            name: admin.name
          }
        })
        
      {:error, :invalid_credentials} ->
        conn
        |> put_status(:unauthorized)
        |> json(%{
          success: false,
          error: "Invalid email or password"
        })
    end
  end

  def login(conn, _params) do
    conn
    |> put_status(:bad_request)
    |> json(%{
      success: false,
      error: "Email and password are required"
    })
  end

  def verify_token(conn, %{"token" => token}) do
    case Guardian.decode_and_verify(token) do
      {:ok, claims} ->
        case Guardian.resource_from_claims(claims) do
          {:ok, admin} ->
            conn
            |> put_status(:ok)
            |> json(%{
              success: true,
              valid: true,
              admin: %{
                email: admin.email,
                name: admin.name
              }
            })
            
          {:error, _reason} ->
            conn
            |> put_status(:unauthorized)
            |> json(%{
              success: false,
              valid: false,
              error: "Invalid token"
            })
        end
        
      {:error, _reason} ->
        conn
        |> put_status(:unauthorized)
        |> json(%{
          success: false,
          valid: false,
          error: "Invalid or expired token"
        })
    end
  end

  def verify_token(conn, _params) do
    conn
    |> put_status(:bad_request)
    |> json(%{
      success: false,
      error: "Token is required"
    })
  end
end