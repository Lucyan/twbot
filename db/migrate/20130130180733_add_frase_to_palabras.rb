class AddFraseToPalabras < ActiveRecord::Migration
  def change
    add_column :palabras, :frase, :string
  end
end
