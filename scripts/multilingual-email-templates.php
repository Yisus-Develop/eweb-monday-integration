<?php
// multilingual-email-templates.php
// Sistema de plantillas de email multilingües para Mars Challenge

class MultilingualEmailTemplates {
    
    // Plantillas para HOT Leads en diferentes idiomas
    private static $hotLeadTemplates = [
        'es' => [
            'subject' => '¡Gracias por tu interés en el Mars Challenge!',
            'body' => "Hola {name},\n\n"
                    . "¡Gracias por tu interés en el Mars Challenge! Tu perfil ha sido clasificado como prioritario (HOT Lead).\n\n"
                    . "Estamos emocionados de saber que estás interesado en ser parte de esta revolución educativa. Pronto nos pondremos en contacto contigo.\n\n"
                    . "Mientras tanto, te invitamos a:\n"
                    . "- Visitar nuestro sitio web: {website_url}\n"
                    . "- Conocer más sobre el proyecto: {project_url}\n\n"
                    . "Saludos cordiales,\n"
                    . "El equipo del Mars Challenge"
        ],
        'pt' => [
            'subject' => 'Obrigado pelo seu interesse no Mars Challenge!',
            'body' => "Olá {name},\n\n"
                    . "Obrigado pelo seu interesse no Mars Challenge! Seu perfil foi classificado como prioritário (HOT Lead).\n\n"
                    . "Estamos animados em saber que você está interessado em fazer parte desta revolução educacional. Em breve entraremos em contato.\n\n"
                    . "Enquanto isso, convidamos você a:\n"
                    . "- Visitar nosso site: {website_url}\n"
                    . "- Conhecer mais sobre o projeto: {project_url}\n\n"
                    . "Atenciosamente,\n"
                    . "Equipe do Mars Challenge"
        ],
        'en' => [
            'subject' => 'Thank you for your interest in the Mars Challenge!',
            'body' => "Hi {name},\n\n"
                    . "Thank you for your interest in the Mars Challenge! Your profile has been classified as priority (HOT Lead).\n\n"
                    . "We're excited to know you're interested in being part of this educational revolution. We'll be in touch soon.\n\n"
                    . "In the meantime, we invite you to:\n"
                    . "- Visit our website: {website_url}\n"
                    . "- Learn more about the project: {project_url}\n\n"
                    . "Best regards,\n"
                    . "The Mars Challenge Team"
        ],
        'fr' => [
            'subject' => 'Merci pour votre intérêt dans le Mars Challenge !',
            'body' => "Bonjour {name},\n\n"
                    . "Merci pour votre intérêt dans le Mars Challenge ! Votre profil a été classé comme prioritaire (HOT Lead).\n\n"
                    . "Nous sommes ravis d'apprendre que vous souhaitez participer à cette révolution éducative. Nous vous contacterons bientôt.\n\n"
                    . "En attendant, nous vous invitons à :\n"
                    . "- Visiter notre site web : {website_url}\n"
                    . "- En savoir plus sur le projet : {project_url}\n\n"
                    . "Cordialement,\n"
                    . "L'équipe du Mars Challenge"
        ]
    ];
    
    // Plantillas para WARM Leads
    private static $warmLeadTemplates = [
        'es' => [
            'subject' => 'Bienvenido al Mars Challenge',
            'body' => "Hola {name},\n\n"
                    . "Gracias por tu interés en el Mars Challenge. Hemos registrado tu información y estás clasificado como un lead de interés (WARM Lead).\n\n"
                    . "Mantente atento a nuestras comunicaciones donde te informaremos sobre las novedades del proyecto.\n\n"
                    . "Si tienes alguna pregunta, no dudes en contactarnos.\n\n"
                    . "Saludos cordiales,\n"
                    . "El equipo del Mars Challenge"
        ],
        'pt' => [
            'subject' => 'Bem-vindo ao Mars Challenge',
            'body' => "Olá {name},\n\n"
                    . "Obrigado pelo seu interesse no Mars Challenge. Registramos sua informação e você está classificado como um lead de interesse (WARM Lead).\n\n"
                    . "Fique atento às nossas comunicações onde informaremos as novidades do projeto.\n\n"
                    . "Se tiver alguma dúvida, não hesite em nos contactar.\n\n"
                    . "Atenciosamente,\n"
                    . "Equipe do Mars Challenge"
        ],
        'en' => [
            'subject' => 'Welcome to the Mars Challenge',
            'body' => "Hi {name},\n\n"
                    . "Thank you for your interest in the Mars Challenge. We've registered your information and you're classified as an interest lead (WARM Lead).\n\n"
                    . "Stay tuned to our communications where we'll inform you about project updates.\n\n"
                    . "If you have any questions, please don't hesitate to contact us.\n\n"
                    . "Best regards,\n"
                    . "The Mars Challenge Team"
        ],
        'fr' => [
            'subject' => 'Bienvenue au Mars Challenge',
            'body' => "Bonjour {name},\n\n"
                    . "Merci pour votre intérêt dans le Mars Challenge. Nous avons enregistré vos informations et vous êtes classé comme lead d'intérêt (WARM Lead).\n\n"
                    . "Restez à l'écoute de nos communications où nous vous informerons des mises à jour du projet.\n\n"
                    . "Si vous avez des questions, n'hésitez pas à nous contacter.\n\n"
                    . "Cordialement,\n"
                    . "L'équipe du Mars Challenge"
        ]
    ];
    
    // Plantillas para COLD Leads
    private static $coldLeadTemplates = [
        'es' => [
            'subject' => 'Mars Challenge - Confirmación de registro',
            'body' => "Hola {name},\n\n"
                    . "Hemos recibido tu información y te agradecemos tu interés en el Mars Challenge.\n\n"
                    . "Mantendremos tus datos registrados y te contactaremos en caso de nuevas oportunidades relacionadas.\n\n"
                    . "Te invitamos a seguir nuestras redes sociales para mantenerte informado.\n\n"
                    . "Saludos cordiales,\n"
                    . "El equipo del Mars Challenge"
        ],
        'pt' => [
            'subject' => 'Mars Challenge - Confirmação de registro',
            'body' => "Olá {name},\n\n"
                    . "Recebemos sua informação e agradecemos seu interesse no Mars Challenge.\n\n"
                    . "Manteremos seus dados registrados e entraremos em contato em caso de novas oportunidades relacionadas.\n\n"
                    . "Convidamos você a seguir nossas redes sociais para se manter informado.\n\n"
                    . "Atenciosamente,\n"
                    . "Equipe do Mars Challenge"
        ],
        'en' => [
            'subject' => 'Mars Challenge - Registration Confirmation',
            'body' => "Hi {name},\n\n"
                    . "We've received your information and thank you for your interest in the Mars Challenge.\n\n"
                    . "We'll keep your data registered and contact you in case of related opportunities.\n\n"
                    . "We invite you to follow our social networks to stay informed.\n\n"
                    . "Best regards,\n"
                    . "The Mars Challenge Team"
        ],
        'fr' => [
            'subject' => 'Mars Challenge - Confirmation d\'inscription',
            'body' => "Bonjour {name},\n\n"
                    . "Nous avons reçu vos informations et vous remercions pour votre intérêt dans le Mars Challenge.\n\n"
                    . "Nous conserverons vos données enregistrées et vous contacterons en cas d'opportunités liées.\n\n"
                    . "Nous vous invitons à suivre nos réseaux sociaux pour rester informé.\n\n"
                    . "Cordialement,\n"
                    . "L'équipe du Mars Challenge"
        ]
    ];
    
    public static function getTemplate($leadType, $language) {
        $templates = [];
        switch ($leadType) {
            case 'HOT':
                $templates = self::$hotLeadTemplates;
                break;
            case 'WARM':
                $templates = self::$warmLeadTemplates;
                break;
            case 'COLD':
                $templates = self::$coldLeadTemplates;
                break;
            default:
                return null;
        }
        
        // Si no existe la plantilla para el idioma exacto, usar español como fallback
        $lang = $language;
        if (!isset($templates[$lang])) {
            $lang = 'es';
        }
        
        return [
            'subject' => $templates[$lang]['subject'],
            'body' => $templates[$lang]['body']
        ];
    }
    
    public static function getAvailableLanguages() {
        $allLanguages = [];
        foreach (self::$hotLeadTemplates as $lang => $template) {
            $allLanguages[$lang] = true;
        }
        return array_keys($allLanguages);
    }
}

// Ejemplo de uso
echo "=== SISTEMA DE PLANTILLAS DE EMAIL MULTILINGÜES ===\n\n";

$languages = MultilingualEmailTemplates::getAvailableLanguages();
foreach ($languages as $lang) {
    echo "Plantillas en idioma: $lang\n";
    
    $hotTemplate = MultilingualEmailTemplates::getTemplate('HOT', $lang);
    echo "  - HOT Lead: " . $hotTemplate['subject'] . "\n";
    
    $warmTemplate = MultilingualEmailTemplates::getTemplate('WARM', $lang);
    echo "  - WARM Lead: " . $warmTemplate['subject'] . "\n";
    
    $coldTemplate = MultilingualEmailTemplates::getTemplate('COLD', $lang);
    echo "  - COLD Lead: " . $coldTemplate['subject'] . "\n";
    
    echo "\n";
}

echo "=== EJEMPLO DE USO ===\n";
echo "Lead HOT en español:\n";
$template = MultilingualEmailTemplates::getTemplate('HOT', 'es');
echo "Asunto: " . $template['subject'] . "\n";
echo "Cuerpo: " . $template['body'] . "\n\n";

echo "Lead WARM en portugués:\n";
$template = MultilingualEmailTemplates::getTemplate('WARM', 'pt');
echo "Asunto: " . $template['subject'] . "\n";
echo "Cuerpo: " . $template['body'] . "\n\n";

?>