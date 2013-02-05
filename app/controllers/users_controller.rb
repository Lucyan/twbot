class UsersController < ApplicationController
  protect_from_forgery
  before_filter :verifica_perfil

  # Verficia perfil de usuario
  def verifica_perfil
    @user = User.find(session[:login]);
    if @user.perfil != 1
      redirect_to(root_path)
    end
  end

  # Listado de Usuarios
  def index
    @usuarios = User.all
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

  # Guardar Usuario editado
  def guardar_editado
    @usuario = User.find(params[:id])
    if @usuario.update_attributes(params[:user])
      redirect_to(usuarios_path, :notice => "Usuario Actualizado")
    else
      render 'editar'
    end
  end
end
