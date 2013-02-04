class AddFechaRenovacionToBots < ActiveRecord::Migration
  def change
    add_column :bots, :fecha_renovacion, :date
  end
end
