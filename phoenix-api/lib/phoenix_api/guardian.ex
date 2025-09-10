defmodule PhoenixApi.Guardian do
  use Guardian, otp_app: :phoenix_api

  def subject_for_token(%{email: email}, _claims) do
    {:ok, email}
  end

  def subject_for_token(_, _) do
    {:error, :reason_for_error}
  end

  def resource_from_claims(%{"sub" => email}) do
    admin_config = Application.get_env(:phoenix_api, :admin_credentials)
    
    if admin_config[:email] == email do
      {:ok, %{
        email: admin_config[:email],
        name: admin_config[:name]
      }}
    else
      {:error, :resource_not_found}
    end
  end

  def resource_from_claims(_claims) do
    {:error, :reason_for_error}
  end

  def authenticate(email, password) do
    admin_config = Application.get_env(:phoenix_api, :admin_credentials)
    
    if admin_config[:email] == email and admin_config[:password] == password do
      admin = %{
        email: admin_config[:email],
        name: admin_config[:name]
      }
      {:ok, admin}
    else
      {:error, :invalid_credentials}
    end
  end
end