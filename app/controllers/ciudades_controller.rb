# Controlador de las ciudades
class CiudadesController < ApplicationController
  protect_from_forgery
  before_filter :recuperar_ciudad, :only => [:editar, :actualizar, :eliminar]
  before_filter :verifica_perfil

  # Verficia perfil de usuario, solo los administradores pueden acceder a las funciones de ciudades
  def verifica_perfil
    user = User.find(session[:login]);
    if user.perfil != 1
      redirect_to(bot_path)
    end
  end

  # Recupera una ciudad especifica
  def recuperar_ciudad
    @ciudad = Ciudad.find(params[:id])
  end

  # Index del controlador, muestra todas las ciudades
  def index
    @ciudades = Ciudad.all
  end

  # Despliega formulario para agregar nueva ciudad
  def nueva
    @ciudad = Ciudad.new
  end

  # Guarda la ciudad nueva
  def guardar
    @ciudad = Ciudad.new(params[:ciudad])
    if @ciudad.valid?
      @ciudad.save
      redirect_to(ciudades_path, :notice => "Nueva Ciudad Agregada")
    else
      flash[:error] = "Error en los datos ingresados"
      render 'nueva'
    end
  end

  # Desplica formulario para editar ciudad
  def editar
  end

  # Guarda ciudad actualizada
  def actualizar
    if @ciudad.update_attributes(params[:ciudad])
      redirect_to(ciudades_path, :notice => "Ciudad Actualizada")
    else
      flash[:error] = "Error en los datos ingresados"
      render 'edit'
    end
  end

  # Elimina una ciudad
  def eliminar
    @ciudad.destroy
    redirect_to(ciudades_path, :notice => "Ciudad Eliminada")
  end
end
