# Modelo de las palabras asociadas a los bots.
class Palabra < ActiveRecord::Base
  attr_accessible :bot_id, :palabra, :frase
  belongs_to :bot
end
