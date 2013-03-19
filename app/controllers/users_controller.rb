# Controlador de usuarios
class UsersController < ApplicationController
  protect_from_forgery
  before_filter :verifica_perfil

  # Verficia perfil de usuario
  def verifica_perfil
    @user = User.find(session[:login])
	
  	begin
  	@auth_user = User.find(params[:id])
  	if @user.perfil != 1
  		if action_name != 'ver'
  			redirect_to(bot_path)
  		else	if @auth_user != @user
  			redirect_to(bot_path)	
  			end	
  		end		
  	end
  	rescue
  		if @user.perfil != 1
  			redirect_to(bot_path)
  		end
  	end
  end

  # Listado de Usuarios
  def index
    @usuarios = User.all
    @variables = Variable.all
  end

  # Elimina Usuarios del Sistema
  def eliminar
    usuario = User.find(params[:id])
    usuario.bots.each {|bot|
      bot.palabras.destroy_all
      bot.botCiudads.destroy_all
      bot.tweets.destroy_all
      bot.destroy
    }
    usuario.destroy    
    redirect_to(usuarios_path, :notice => "Bot Eliminado")
  end

  # Editar Usuario
  def editar
    @usuario = User.find(params[:id])
  end
  
  #Ver Usuario
  def ver
	@usuario = User.find(params[:id])
  end

  # Guardar Usuario editado
  def guardar_editado
    @usuario = User.find(params[:id])
    if @usuario.update_attributes(params[:user])
      redirect_to(usuarios_path, :notice => "Usuario Actualizado")
    else
      render 'editar'
    end
  end 

  # Guarda valor de nueva variable
  def guardar_variable
    variable = Variable.find(params[:id])
    if variable.update_attributes(params[:variable])
      redirect_to(:back, :notice => "Variable actualizada")
    else
      redirect_to(:back, :notice => "Error al intentar guardar variable, intentalo nuevamente")
    end
  end
end
