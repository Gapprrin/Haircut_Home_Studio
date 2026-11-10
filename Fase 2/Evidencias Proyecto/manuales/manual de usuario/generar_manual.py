# -*- coding: utf-8 -*-
"""Genera Manual_de_Usuario.docx (un solo documento, 3 roles)."""
from pathlib import Path

from docx import Document
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml.ns import qn
from docx.shared import Cm, Pt, RGBColor

BASE = Path(__file__).resolve().parent
CAP = BASE / "capturas"
OUT = BASE / "Manual_de_Usuario.docx"


def set_run(run, size=11, bold=False, color=None):
    run.font.name = "Calibri"
    run._element.rPr.rFonts.set(qn("w:eastAsia"), "Calibri")
    run.font.size = Pt(size)
    run.bold = bold
    if color:
        run.font.color.rgb = color


def add_title(doc, text, size=22):
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run(text)
    set_run(r, size=size, bold=True, color=RGBColor(0x5D, 0x2E, 0x3C))
    return p


def add_h(doc, text, level=1):
    heading = doc.add_heading(text, level=level)
    for run in heading.runs:
        set_run(run, size=16 if level == 1 else 13, bold=True, color=RGBColor(0x5D, 0x2E, 0x3C))
    return heading


def add_p(doc, text):
    p = doc.add_paragraph()
    r = p.add_run(text)
    set_run(r, size=11)
    p.paragraph_format.space_after = Pt(6)
    return p


def add_bullets(doc, items):
    for item in items:
        p = doc.add_paragraph(style="List Bullet")
        r = p.add_run(item)
        set_run(r, size=11)


def add_img(doc, rel_path, width_cm=12.5, caption=None):
    path = CAP / rel_path
    if not path.exists():
        add_p(doc, f"[Captura no encontrada: {rel_path}]")
        return
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = p.add_run()
    run.add_picture(str(path), width=Cm(width_cm))
    if caption:
        c = doc.add_paragraph()
        c.alignment = WD_ALIGN_PARAGRAPH.CENTER
        r = c.add_run(caption)
        set_run(r, size=9, color=RGBColor(0x66, 0x66, 0x66))


def main():
    doc = Document()
    section = doc.sections[0]
    section.top_margin = Cm(1.8)
    section.bottom_margin = Cm(1.8)
    section.left_margin = Cm(2)
    section.right_margin = Cm(2)

    add_title(doc, "HairCut Home Studio", 26)
    add_title(doc, "Manual de Usuario", 20)
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("Versión Fase 2 · Melipilla · Un solo documento para Administrador, Peluquero y Cliente")
    set_run(r, size=10, color=RGBColor(0x66, 0x66, 0x66))

    add_h(doc, "1. Introducción", 1)
    add_p(
        doc,
        "Este manual describe las pantallas y acciones principales del sistema web HairCut Home Studio. "
        "La aplicación permite reservar horas, revisar productos, gestionar la agenda del estudio y "
        "usar el simulador de estilo con inteligencia artificial.",
    )
    add_p(doc, "URL local de referencia: http://localhost:8000")

    add_h(doc, "2. Acceso común", 1)
    add_p(doc, "Cualquier persona puede abrir el sitio, ver la vitrina de trabajos y el catálogo de productos.")
    add_img(doc, "comun/01-inicio.png", caption="Inicio del sitio")
    add_p(doc, "Para entrar a funciones privadas: Menú → Entrar (o Reservar / Simulador si aún no hay sesión).")
    add_img(doc, "comun/02-menu.png", caption="Menú lateral (ejemplo con sesión de cliente)")
    add_img(doc, "comun/03-login.png", caption="Pantalla Entrar")
    add_p(doc, "Cuentas de ejemplo usadas en este manual:")
    add_bullets(
        doc,
        [
            "Administrador: admin@admin.cl / admin123",
            "Peluquero Ricardo: ricardo@me.com / kako123",
            "Clienta M.: clienta.m@haircut.cl / ClientaM123",
        ],
    )

    add_h(doc, "3. Rol Administrador", 1)
    add_p(
        doc,
        "Tras iniciar sesión como administrador, el sistema muestra la bienvenida y da acceso a "
        "Servicios, Catálogo y Personas desde el menú.",
    )
    add_img(doc, "admin/admin-01-bienvenida.png", caption="Bienvenida del administrador")

    add_h(doc, "3.1 Servicios", 2)
    add_p(doc, "Desde Servicios puedes crear categorías, agregar servicios, editar precios/descripciones y eliminar ítems.")
    add_bullets(
        doc,
        [
            "Agregar categoría / servicio con el botón correspondiente.",
            "Editar: lápiz en la ficha del servicio.",
            "Eliminar: botón Eliminar (pide confirmación).",
        ],
    )
    add_img(doc, "admin/admin-02-servicios.png", caption="Gestión de servicios")

    add_h(doc, "3.2 Catálogo de productos", 2)
    add_p(
        doc,
        "En Productos administras el catálogo visible en la tienda presencial (Pravana, Bohus, etc.). "
        "Puedes buscar por nombre, agregar, editar o eliminar, y marcar visibilidad.",
    )
    add_bullets(
        doc,
        [
            "Agregar producto +",
            "Busca por nombre",
            "Editar / Eliminar en cada ficha",
        ],
    )
    add_img(doc, "admin/admin-03-catalogo.png", caption="Catálogo de productos")

    add_h(doc, "3.3 Personas (usuarios)", 2)
    add_p(
        doc,
        "La pantalla Personas permite crear cuentas con rol Admin, Peluquero o Cliente, filtrar por rol, "
        "buscar y editar o eliminar (excepto tu propia cuenta, que no se elimina).",
    )
    add_bullets(
        doc,
        [
            "Agregar persona +",
            "Filtros: Todas / Admin / Peluquero / Cliente / CliAdmin",
            "Editar (nombre, correo, rol, contraseña opcional)",
            "Eliminar (con confirmación)",
        ],
    )
    add_img(doc, "admin/admin-04-personas.png", caption="Personas — acciones Editar y Eliminar")
    add_p(doc, "Cerrar sesión: Menú → Cerrar sesión → confirmar.")

    add_h(doc, "4. Rol Peluquero (Ricardo)", 1)
    add_p(
        doc,
        "Ricardo gestiona la agenda del estudio: estadísticas, disponibilidad, solicitudes y horas confirmadas.",
    )
    add_img(doc, "ricardo/ricardo-01-bienvenida.png", caption="Bienvenida Ricardo")

    add_h(doc, "4.1 Panel / Estadísticas", 2)
    add_p(
        doc,
        "El panel muestra pendientes del día, confirmadas, ingresos estimados y permite descargar reporte PDF o Excel del mes.",
    )
    add_img(doc, "ricardo/ricardo-02-panel.png", caption="Panel — resumen del studio")

    add_h(doc, "4.2 Solicitudes", 2)
    add_p(
        doc,
        "Aquí aparecen las reservas pendientes. Por cada solicitud puedes ver datos del cliente, servicio, "
        "fecha/hora, fotos de referencia o Simulación IA, y luego Confirmar o Rechazar.",
    )
    add_img(doc, "ricardo/ricardo-03-solicitudes.png", caption="Solicitudes pendientes — Confirmar / Rechazar")

    add_h(doc, "4.3 Disponibilidad", 2)
    add_p(
        doc,
        "Marca días libres (feriados/vacaciones), define horario de atención, días abiertos y meses futuros "
        "visibles para las clientas. Guarda con el botón Guardar.",
    )
    add_img(doc, "ricardo/ricardo-04-disponibilidad.png", caption="Disponibilidad — calendario y horario")

    add_h(doc, "4.4 Horas", 2)
    add_p(doc, "Lista las horas confirmadas hacia adelante. Las de días terminados se ocultan solas.")
    add_img(doc, "ricardo/ricardo-05-horas.png", caption="Horas agendadas")

    add_h(doc, "5. Rol Cliente (Clienta M.)", 1)
    add_p(
        doc,
        "Con sesión de cliente el menú ofrece: Inicio, Productos, Simulador, Reservar y Mis reservas. "
        "También puedes abrir tu perfil (nombre Clienta M.) y cerrar sesión.",
    )
    add_img(doc, "clienta-m/clienta-01-menu.png", caption="Menú de Clienta M. — acciones de pantalla de usuario")

    add_h(doc, "5.1 Simulador de estilo", 2)
    add_p(
        doc,
        "El simulador permite probar un cambio de cabello antes de reservar. Pasos:",
    )
    add_bullets(
        doc,
        [
            "Abrir Menú → Simulador.",
            "Tomar foto o Elegir de galería (en este manual se usó la foto de ejemplo de hombre).",
            "Elegir categoría Corte y la opción Corte de hombres.",
            "Marcar la autorización de procesamiento de la fotografía.",
            "Pulsar Generar simulación.",
            "Revisar Original vs Simulación IA; opciones Usar al reservar, Descargar o Eliminar imágenes.",
        ],
    )
    add_img(doc, "clienta-m/clienta-02-simulador.png", caption="Simulador vacío — Tomar foto / Elegir de galería")
    add_img(doc, "clienta-m/clienta-03-simulador-foto.png", caption="Foto cargada en el simulador")
    add_img(doc, "clienta-m/clienta-04-corte-hombre.png", caption="Selección: Corte de hombres + autorización")
    add_img(
        doc,
        "clienta-m/clienta-05-resultado.png",
        caption="Resultado: Original (izquierda) y Simulación IA con corte de hombre (derecha)",
        width_cm=13.5,
    )

    add_h(doc, "5.2 Reservar hora", 2)
    add_p(
        doc,
        "Flujo en 5 pasos: Servicio → Fecha/hora → Imágenes de referencia (hasta 2, opcional) → "
        "Lugar (salón o domicilio) → Confirmar. Usa Siguiente para avanzar. El pago es presencial.",
    )
    add_img(doc, "clienta-m/clienta-06-reservar.png", caption="Reservar — paso 1 elegir servicio")

    add_h(doc, "5.3 Mis reservas", 2)
    add_p(
        doc,
        "Consulta el estado de tus solicitudes. Si no hay ninguna, el sistema invita a crear la primera. "
        "Puedes cancelar una reserva confirmada hasta 10 minutos antes de la hora.",
    )
    add_img(doc, "clienta-m/clienta-07-mis-reservas.png", caption="Mis reservas")

    add_h(doc, "6. Resumen de acciones por pantalla", 1)
    add_bullets(
        doc,
        [
            "Header: logo (inicio), Volver atrás, Menú.",
            "Menú: perfil, Cerrar sesión, enlaces según rol.",
            "Admin Personas: Agregar, filtrar, Editar, Eliminar.",
            "Ricardo Solicitudes: Confirmar, Rechazar, ver Simulación IA / referencias.",
            "Cliente Simulador: cámara/galería, elegir servicio, autorizar, generar, descargar/usar al reservar.",
            "Cliente Reservar: pasos 1–5 y Siguiente / Confirmar.",
        ],
    )

    add_h(doc, "7. Notas", 1)
    add_bullets(
        doc,
        [
            "Las capturas corresponden al entorno local Docker (puerto 8000).",
            "La simulación IA es referencial; el profesional valida el resultado final.",
            "Los productos del catálogo se adquieren solo de forma presencial.",
        ],
    )

    doc.save(OUT)
    print(f"Generado: {OUT}")


if __name__ == "__main__":
    main()
