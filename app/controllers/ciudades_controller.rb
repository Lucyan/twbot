class CiudadesController < ApplicationController
  protect_from_forgery
  before_filter :recuperar_ciudad, :only => [:editar, :actualizar, :eliminar]
  before_filter :verifica_perfil

  #Verficia perfil de usuario
  def verifica_perfil
    user = User.find(session[:login]);
    if user.perfil != 1
      redirect_to(root_path)
    end
  end

  def recuperar_ciudad
    @ciudad = Ciudad.find(params[:id])
  end

  def index
    @ciudades = Ciudad.all
  end

  def nueva
    @ciudad = Ciudad.new
  end

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

  def editar
  end

  def actualizar
    if @ciudad.update_attributes(params[:ciudad])
      redirect_to(ciudades_path, :notice => "Ciudad Actualizada")
    else
      flash[:error] = "Error en los datos ingresados"
      render 'edit'
    end
  end

  def eliminar
    @ciudad.destroy
    redirect_to(ciudades_path, :notice => "Ciudad Eliminada")
  end
end
