class UsersController < ApplicationController
	protect_from_forgery
  	before_filter :verifica_perfil

  	#Verficia perfil de usuario
  	def verifica_perfil
  		user = User.find(session[:login]);
  		if user.perfil != 1
  			redirect_to(root_path)
  		end
  	end

  	def index
  		@usuarios = User.all
  	end
end
