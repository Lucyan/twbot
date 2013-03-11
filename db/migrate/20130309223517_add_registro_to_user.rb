class AddRegistroToUser < ActiveRecord::Migration
  def change
    add_column :users, :registro, :boolean, :default => false
  end
end
