defmodule PhoenixApiWeb.PageController do
  use PhoenixApiWeb, :controller

  def home(conn, _params) do
    render(conn, :home)
  end
end
