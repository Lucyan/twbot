class AddPerfilToUsers < ActiveRecord::Migration
  def change
    add_column :users, :perfil, :integer, :default => 0
  end
end
