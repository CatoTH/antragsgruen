<?php

return [
    'my_acc_title'         => 'Mi cuenta',
    'my_acc_bread'         => 'Configuración',
    'email_address'        => 'Dirección de correo electrónico',
    'email_address_new'    => 'Nueva dirección de correo electrónico',
    'email_blocklist'      => 'Bloquear todos los correos a esta cuenta',
    'email_unconfirmed'    => 'sin confirmar',
    'pwd_confirm'          => 'Confirmar contraseña',
    'pwd_change'           => 'Cambiar contraseña',
    'pwd_change_hint'      => 'Vacío = dejar sin cambios',
    'organisation_primary' => 'Organización principal',
    'name'                 => 'Nombre',
    'name_given'           => 'Nombre de pila',
    'name_family'          => 'Apellidos',
    'organization'         => 'Organización',
    'user_group'           => 'Grupo de usuarios',
    'user_groups'          => 'Grupos de usuarios',
    'user_groups_con'      => 'Este evento',
    'user_groups_system'   => 'General',

    '2fa_title'                => 'Autenticación de dos factores',
    '2fa_off'                  => 'No activa',
    '2fa_activate_opener'      => 'Activar',
    '2fa_activated'            => 'Configurada',
    '2fa_remove_open'          => 'Desactivar la autenticación de dos factores',
    '2fa_remove_code'          => 'Introduce el código actual para desactivar',
    '2fa_add_explanation'      => 'Puedes proteger tu cuenta añadiendo un segundo factor además de tu contraseña.',
    '2fa_add_step1'            => '1. Escanea el código QR con la aplicación',
    '2fa_add_step2'            => '2. Introduce el código generado',
    '2fa_img_alt'              => 'Código QR para TOTP; escanea este código con la aplicación de autenticación de dos factores que prefieras',
    '2fa_enter_code'           => 'Introduce el código',
    '2fa_register_title'       => 'Configurar la autenticación de dos factores',
    '2fa_register_explanation' => 'Es necesario proteger tu cuenta con un segundo factor para poder usarla.',
    '2fa_general_explanation'  => 'Usa una aplicación como <a href="https://authy.com/download/">Authy</a>, <a href="https://freeotp.github.io">FreeOTP</a> (código abierto), <a href="https://www.microsoft.com/de-de/security/mobile-authenticator-app">Microsoft Authenticator</a> o Google Authenticator.<br><br>Escanea el código QR de abajo, guárdalo en tu teléfono y luego introduce aquí, más abajo en esta página, el código numérico generado.',
    '2fa_login_intro'          => 'Has protegido tu cuenta con un segundo factor. Abre la aplicación que usaste para configurarlo e introduce el código que muestra:',

    'force_pwd_title'       => 'Cambiar contraseña',
    'force_pwd_explanation' => 'Establece una nueva contraseña para poder usar Antragsgrün.',

    'username_deleted' => 'Eliminado',

    'err_pwd_different'       => 'Las dos contraseñas no coinciden.',
    'err_pwd_length'          => 'La contraseña debe tener al menos %MINLEN% caracteres.',
    'err_pwd_fixed'           => 'La contraseña de esta cuenta no se puede cambiar y, por lo tanto, tampoco restablecer. Ponte en contacto con la administración del sistema para obtener ayuda.',
    'err_user_acode_notfound' => 'Usuario no encontrado / código no válido',
    'err_user_notfound'       => 'No se encontró la cuenta de usuario %USER%.',
    'err_code_wrong'          => 'El código introducido no es válido.',
    'pwd_recovery_sent'       => 'Se ha enviado un correo electrónico para recuperar la contraseña.',
    'welcome'                 => '¡Bienvenido/a!',
    'err_email_acc_notfound'  => 'No hay ninguna cuenta con esta dirección de correo electrónico...?',
    'err_email_acc_confirmed' => 'Esta cuenta ya está confirmada.',
    'err_invalid_email'       => 'La dirección de correo electrónico introducida no es válida',
    'err_unknown'              => 'Se produjo un error desconocido',
    'err_unknown_ww_repeat'    => 'Se produjo un error desconocido.',
    'err_no_recovery'          => 'No se ha enviado ninguna solicitud de recuperación en las últimas 24 horas.',
    'err_change_toolong'       => 'La solicitud es demasiado antigua; solicita un nuevo cambio y confirma el correo electrónico dentro de las 24 horas',
    'err_2fa_nosession_user'   => 'No se encontró ningún proceso de registro TOTP en curso para el usuario actual',
    'err_2fa_nosession'        => 'No hay ninguna sesión de inicio de sesión en curso',
    'err_2fa_timeout'          => 'Confirma el segundo factor dentro de %minutes% minutos.',
    'err_2fa_empty'            => 'Código vacío',
    'err_2fa_incorrect'        => 'Código incorrecto',
    'err_2fa_nocode'           => 'No hay ningún segundo factor registrado',

    'recover_mail_title' => 'Antragsgrün: recuperación de contraseña',
    'recover_mail_body'  => "¡Hola!\n\nHas solicitado recuperar tu contraseña. " .
        "Para continuar, abre la siguiente página e introduce la nueva contraseña:\n\n%URL%\n\n" .
        "O introduce el siguiente código en la página de recuperación: %CODE%",

    'err_recover_mail_sent'     => 'Ya se ha enviado un correo de recuperación en las últimas 24 horas.',
    'err_emailchange_mail_sent' => 'Ya has solicitado un cambio de correo electrónico en las últimas 24 horas.',
    'err_emailchange_notfound'  => 'No se solicitó ningún cambio de correo electrónico o ya se ha realizado.',
    'err_emailchange_flood'     => 'Para evitar el envío masivo de correos, debe haber al menos 5 minutos entre el envío de dos correos electrónicos',
    'emailchange_mail_title'    => 'Confirmar la nueva dirección de correo electrónico',
    'emailchange_mail_body'     => "¡Hola!\n\nHas solicitado cambiar la dirección de correo electrónico. " .
        "Para continuar, abre la siguiente página:\n\n%URL%\n\n",
    'emailchange_sent'          => 'Se ha enviado un correo de confirmación a esta dirección. ' .
        'Abre el enlace incluido para cambiar la dirección.',
    'emailchange_done'          => 'La dirección de correo electrónico ha sido cambiada.',
    'emailchange_requested'     => 'Dirección de correo electrónico solicitada (aún no confirmada)',
    'emailchange_call'          => 'cambiar',
    'emailchange_resend'        => 'Nuevo correo de confirmación',
    'email_pp_replyto'          => 'Responder a (Reply-To) al enviar procedimientos propuestos (definido por el administrador)',

    'del_title'       => 'Eliminar cuenta',
    'del_explanation' => 'Aquí puedes eliminar esta cuenta. No recibirás más correos electrónicos, no será posible iniciar sesión después de esto.
        Tu dirección de correo electrónico, nombre y datos de contacto serán eliminados.<br>
        Las mociones y enmiendas que hayas presentado seguirán siendo visibles. Para retirar mociones ya presentadas, ponte en contacto con la administración del evento correspondiente.',
    'del_confirm'     => 'Confirmar eliminación',
    'del_do'          => 'Eliminar',

    'noti_greeting'         => 'Hola %NAME%,',
    'noti_bye'              => "Un saludo,\n   El equipo de Antragsgrün\n\n--\n\n" .
        "Si no quieres recibir más correos electrónicos, puedes darte de baja aquí:\n",
    'noti_new_motion_title' => '[Antragsgrün] Nueva moción:',
    'noti_new_motion_body'  => "Se ha presentado una nueva moción:\nEvento: %CONSULTATION%\n" .
        "Nombre: %TITLE%\nAutor: %INITIATOR%\nEnlace: %LINK%",
    'noti_new_amend_title'  => '[Antragsgrün] Nueva enmienda a %TITLE%',
    'noti_new_amend_body'   => "Se ha presentado una nueva enmienda:\nEvento: %CONSULTATION%\n" .
        "Moción: %TITLE%\nEnlace: %LINK%",
    'noti_amend_mymotion'       => "Se ha publicado una nueva enmienda a tu moción:\nEvento: %CONSULTATION%\n" .
        "Moción: %TITLE%\nAutor: %INITIATOR%\nEnlace: %LINK%\n%MERGE_HINT%",
    'noti_amend_mymotion_merge' => "\nSi estás de acuerdo con esta enmienda, puedes incorporar los cambios (\"Incorporar cambios a la moción\" en la barra lateral)",
    'noti_new_comment_title'    => '[Antragsgrün] Nuevo comentario a %TITLE%',
    'noti_new_comment_body'     => "Se comentó %TITLE%:\n%LINK%",
    'acc_grant_email_title'     => 'Acceso a Antragsgrün',
    'acc_grant_email_userdata'  => "Correo electrónico / nombre de usuario: %EMAIL%\nContraseña: %PASSWORD%",

    'login_title'             => 'Iniciar sesión',
    'login_con_pwd_title'     => 'Iniciar sesión con la contraseña del evento',
    'login_con_pwd'           => 'Contraseña del evento',
    'login_username_title'    => 'Iniciar sesión con nombre de usuario/contraseña',
    'login_create_account'    => 'Crear una cuenta nueva',
    'login_username'          => 'Dirección de correo electrónico / nombre de usuario',
    'login_email_placeholder' => 'Tu dirección de correo electrónico',
    'login_password'          => 'Contraseña',
    'login_password_rep'      => 'Contraseña (confirmación)',
    'login_create_name'       => 'Tu nombre',
    'login_captcha'           => 'Introduce el código mostrado',
    'login_btn_login'         => 'Iniciar sesión',
    'login_btn_create'        => 'Crear',
    'login_forgot_pw'         => '¿Olvidaste tu contraseña?',
    'login_openid'            => 'Inicio de sesión con OpenID',
    'login_openid_url'        => 'URL de OpenID',
    'login_managed_hint'      => '<strong>Nota:</strong> las cuentas nuevas deben ser revisadas por un administrador antes de poder acceder a este sitio.',

    'managed_account_ask_btn' => 'Solicitar permiso',
    'managed_account_asked'   => 'Permiso solicitado.',

    'acc_request_noti_subject' => 'Solicitud: acceso al sitio',
    'acc_request_noti_body'    => 'El usuario %USERNAME% (%EMAIL%) solicita acceso al sitio «%CONSULTATION%». En la siguiente página puedes conceder el permiso: %ACTIONLINK%',

    'login_confirm_registration' => 'Confirmar el registro',

    'login_err_password'   => 'Contraseña no válida.',
    'login_err_username'   => 'Nombre de usuario no encontrado.',
    'login_err_siteaccess' => 'Esta cuenta no puede iniciar sesión en este sitio.',
    'login_err_captcha'    => 'El código introducido no coincide con la imagen',
    'login_err_nocaptcha'  => 'Introduce el código que se muestra en la imagen.',

    'create_err_emailexists'  => 'Esta dirección de correo electrónico ya está registrada en otra cuenta',
    'create_err_siteaccess'   => 'No está permitido crear cuentas para este sitio.',
    'create_err_emailinvalid' => 'Introduce una dirección de correo electrónico válida.',
    'create_err_pwdlength'    => 'La contraseña debe tener al menos %MINLEN% caracteres.',
    'create_err_pwdmismatch'  => 'Las dos contraseñas introducidas no coinciden.',
    'create_err_noname'       => 'Introduce tu nombre.',
    'err_contact_required'    => 'Debes indicar una dirección de contacto.',

    'create_emailconfirm_title' => 'Registro en Antragsgrün / motion.tools',
    'create_emailconfirm_msg'   =>
        "Hola,\n\nhaz clic en el siguiente enlace para confirmar tu cuenta:\n" .
        "%BEST_LINK%\n\n"
        . "...o introduce el siguiente código en el sitio: %CODE%\n\n"
        . "Un saludo,\n\tEquipo de Antragsgrün",

    'access_denied_title'  => 'Sin acceso',
    'access_denied_body'   => 'No tienes acceso a este sitio.',
    'access_granted_email' => "Hola,\n\nAcabas de obtener acceso a: %LINK%\n\n"
        . "Un saludo,\n\tEquipo de Antragsgrün",

    'confirm_title'     => 'Confirma tu cuenta',
    'confirm_username'  => 'Dirección de correo electrónico / nombre de usuario',
    'confirm_mail_sent' => 'Se acaba de enviar un correo electrónico a tu dirección. Confirma la recepción de este correo haciendo clic en el enlace que contiene o introduciendo el código indicado en esta página.',
    'confirm_code'      => 'Código de confirmación',
    'confirm_btn_do'    => 'Confirmar',
    'confirm_resend'    => 'Reenviar correo de confirmación',

    'confirmed_title'         => 'Cuenta confirmada',
    'confirmed_msg'           => '¡Ya está todo listo! Tu cuenta está confirmada y puedes continuar.',
    'confirmed_screening_msg' => 'Tu cuenta ya es válida. Se ha notificado al administrador para que te conceda acceso a este sitio.',

    'recover_title'       => 'Recuperación de contraseña',
    'recover_step1'       => '1. Introduce tu dirección de correo electrónico',
    'recover_email_place' => 'mi@direccion-correo.org',
    'recover_send_email'  => 'Enviar correo de confirmación',
    'recover_step2'       => '2. Establece una nueva contraseña',
    'recover_email'       => 'Dirección de correo electrónico',
    'recover_code'        => 'Código de confirmación',
    'recover_new_pwd'     => 'Nueva contraseña',
    'recover_set_pwd'     => 'Establecer nueva contraseña',

    'recovered_title' => 'Nueva contraseña establecida',
    'recovered_msg'   => 'Tu contraseña ha sido cambiada.',

    'deleted_title' => 'Cuenta eliminada',
    'deleted_msg'   => 'Tu cuenta ha sido eliminada.',

    'no_noti_title'        => 'Darse de baja de las notificaciones',
    'no_noti_bc'           => 'Notificaciones',
    'no_noti_unchanged'    => 'Dejar las notificaciones como están',
    'no_noti_consultation' => 'Darte de baja de las notificaciones de este evento (%NAME%)',
    'no_noti_all'          => 'Darte de baja de todas las notificaciones',
    'no_noti_blocklist'    => 'Ningún correo electrónico en absoluto <small>(incluidos los correos de recuperación de contraseña, etc.)</small>',
    'no_noti_save'         => 'Guardar',

    'notification_title' => 'Notificaciones por correo electrónico',
    'notification_intro' => 'Puedes elegir individualmente para cada evento sobre qué quieres recibir notificaciones:',

    'export_title' => 'Exportación de datos',
    'export_intro' => 'Puedes descargar todos los datos personales guardados sobre ti en discuss.green en un formato JSON legible por máquina.',
    'export_btn'   => 'Descargar',

    'group_template_siteadmin'           => 'Administrador del sitio',
    'group_template_siteadmin_h'         => 'Todos los privilegios sobre todos los eventos de este sitio / subdominio.',
    'group_template_consultationadmin'   => 'Administrador del evento',
    'group_template_consultationadmin_h' => 'Todos los privilegios sobre este evento.',
    'group_template_proposed'            => 'Procedimiento propuesto',
    'group_template_proposed_h'          => 'Puede editar el procedimiento propuesto, pero no las mociones ni las enmiendas.',
    'group_template_progress'            => 'Informes de progreso',
    'group_template_progress_h'          => 'Puede editar los informes de progreso de las resoluciones, pero no las resoluciones en sí.',
    'group_template_participant'         => 'Participante',
    'group_template_participant_h'       => 'Sin privilegios especiales. Solo relevante si el acceso a este sitio está restringido.',

    'pw_x_chars'      => 'La contraseña debe tener al menos %NUM% caracteres.',
    'pw_min_x_chars'  => 'Mín. %NUM% caracteres',
    'pw_no_match'     => 'Las contraseñas no coinciden.',
];
