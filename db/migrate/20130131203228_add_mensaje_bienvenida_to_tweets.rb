class AddMensajeBienvenidaToTweets < ActiveRecord::Migration
  def change
    add_column :tweets, :mensaje_enviado, :int
  end
end
