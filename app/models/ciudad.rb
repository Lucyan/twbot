# Modelo de los datos de las ciudades en el sistema
class Ciudad < ActiveRecord::Base
  attr_accessible :km, :latitud, :longitud, :nombre
  has_many :botCiudads
end
