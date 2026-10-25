<?php

namespace App\utils;

class Regex
{
    // Personas: solo letras (tildes/ñ), espacios, apóstrofe o guion entre palabras
    public const PERSON_NAME = '/^\p{L}+(?:[ \'\-]\p{L}+)*$/u';

    // Títulos y frases cortas: letras, números, espacios y puntuación común
    public const PHRASE = '/^[\p{L}\p{N}][\p{L}\p{N} .,;:¡!¿?()\-\'"\/&%]*$/u';


    // Solo RUC peruano: 11 dígitos
    public const TAX_ID_PE = '/^\d{11}$/';

    // General (empresas extranjeras): 4 a 20 caracteres alfanuméricos, con . - /
    public const GENERAL_TAX_ID = '/^[A-Za-z0-9][A-Za-z0-9.\-\/]{3,19}$/';

    // Número de teléfono compatible con normas internacionales: opcional +, dígitos, espacios y guiones (de 6 a 20 caracteres)
    public const PHONE = '/^\+?\d[\d\s\-]{5,20}$/';

    // Códigos: comienzan con una letra o un número, seguidos de letras, números, guiones o guiones bajos (de 2 a 30 caracteres)
    public const CODE = '/^[A-Za-z0-9][A-Za-z0-9\-_]{1,29}$/';

    // DNI peruano: exactamente 8 dígitos
    public const DOC_DNI = '/^\d{8}$/';

    // Carné de extranjería: 9 a 12 caracteres alfanuméricos
    public const DOC_CE = '/^[A-Za-z0-9]{9,12}$/';

    // Pasaporte: 6 a 12 caracteres alfanuméricos
    public const DOC_PASSPORT = '/^[A-Za-z0-9]{6,12}$/';

    // Otros documentos: 4 a 20 caracteres alfanuméricos o guion
    public const DOC_OTHER = '/^[A-Za-z0-9\-]{4,20}$/';
}
