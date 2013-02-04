class AddPerfilToUsers < ActiveRecord::Migration
  def change
    add_column :users, :perfil, :integer
  end
end
