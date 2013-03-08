# Controlador de paginas estaticas
class PagesController < ApplicationController
	skip_before_filter :autentificacion

	# Indice
	def index
	end

	# Precio
	def pricing
	end
	
	# Sobre esto
	def about
	end
	
	# Tour
	def tour
	end
	
end
