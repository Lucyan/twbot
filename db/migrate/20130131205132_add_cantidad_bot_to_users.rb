class AddCantidadBotToUsers < ActiveRecord::Migration
  def change
    add_column :users, :cantidad_bots, :integer, :default => 1
  end
end
