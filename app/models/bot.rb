# Modelo de la tabla bot en la aplicación, se guardan los datos básicos y de configuración de cada bot.
class Bot < ActiveRecord::Base
  attr_accessible :estado, :nombre, :tw_cuenta, :tw_secret, :tw_token, :siguiendo, :seguidores, :cantidad_seguir, :palabra_indice, 
  :palabra_maximo, :ciudad_indice, :verificar_seguido, :followers_count, :user_id, :fecha_renovacion, :frase_al_seguir, :frase_cuando_siguen, :plus
  has_many :palabras
  has_many :botCiudads
  has_many :tweets
  belongs_to :user

  validates :cantidad_seguir,  presence: true, 
  			numericality: { only_integer: true, 
  							greater_than_or_equal_to: 1 }
end
