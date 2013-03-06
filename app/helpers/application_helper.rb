# Helper de la aplicación, se definene funciones que están disponibles en toda la app
module ApplicationHelper
	# Devuelve el titulo para cada vista
	def full_title(page_title)
		base_title = NOMBRE_APP
		if page_title.empty?
			base_title
		else
			"#{base_title} | #{page_title}"
		end
	end
end
