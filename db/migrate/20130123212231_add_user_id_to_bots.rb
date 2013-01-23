class AddUserIdToBots < ActiveRecord::Migration
  def change
    add_column :bots, :user_id, :integer
  end
end
