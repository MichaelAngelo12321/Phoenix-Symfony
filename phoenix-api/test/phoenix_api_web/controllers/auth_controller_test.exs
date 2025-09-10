defmodule PhoenixApiWeb.AuthControllerTest do
  use PhoenixApiWeb.ConnCase

  describe "login" do
    test "returns JWT token for valid admin credentials", %{conn: conn} do
      conn = post(conn, ~p"/api/auth/login", %{
        "email" => "admin@example.com",
        "password" => "SecureAdminPassword123!"
      })
      
      response = json_response(conn, 200)
      assert response["success"] == true
      assert Map.has_key?(response, "token")
      assert Map.has_key?(response, "admin")
      assert response["admin"]["email"] == "admin@example.com"
    end

    test "returns error for invalid credentials", %{conn: conn} do
      conn = post(conn, ~p"/api/auth/login", %{
        "email" => "admin@example.com",
        "password" => "wrongpassword"
      })
      
      response = json_response(conn, 401)
      assert response["success"] == false
      assert response["error"] == "Invalid email or password"
    end

    test "returns error for missing credentials", %{conn: conn} do
      conn = post(conn, ~p"/api/auth/login", %{})
      
      response = json_response(conn, 400)
      assert response["success"] == false
      assert response["error"] == "Email and password are required"
    end
  end

  # Note: /api/auth/verify endpoint is not implemented yet
  # Tests for verify endpoint would go here when implemented
end