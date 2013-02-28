class AddFraseCuandoSiguenToBots < ActiveRecord::Migration
  def change
    add_column :bots, :frase_cuando_siguen, :string, :default => ''
  end
end
