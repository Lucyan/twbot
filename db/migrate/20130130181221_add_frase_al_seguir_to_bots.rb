class AddFraseAlSeguirToBots < ActiveRecord::Migration
  def change
    add_column :bots, :frase_al_seguir, :string
  end
end
