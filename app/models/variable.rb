# Modelo de tabla variables de sistema
class Variable < ActiveRecord::Base
  attr_accessible :key, :value

  validates :value, presence: true, numericality: { only_integer: true }
end

# Datos principales
# Variable.create(key: "limite_seguir", value: "100")
# Variable.create(key: "limite_mensajes", value: "5")