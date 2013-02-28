class AddPlusToBots < ActiveRecord::Migration
  def change
    add_column :bots, :plus, :boolean, :default => false
  end
end
