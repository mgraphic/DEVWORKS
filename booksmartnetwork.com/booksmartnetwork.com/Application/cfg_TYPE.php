<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class TYPE
{
    const _NOCLEAN             = 0;     // no change
    
    const _BOOL                = 1;     // force boolean
    const _INT                 = 2;     // force non-floating integer
    const _UINT                = 3;     // force non-floating unsigned integer
    const _FLOAT               = 4;     // force floating number
    const _UFLOAT              = 5;     // force unsigned floating number
    const _UNIXTIME            = 6;     // force unix datestamp (unsigned integer)
    const _STR                 = 7;     // force trimmed string
    const _NOTRIM              = 8;     // force string - no trim
    const _NOHTML              = 9;     // force trimmed string with HTML made safe
    const _ARRAY               = 10;    // force array
    const _FILE                = 11;    // force file
    const _BINARY              = 12;    // force binary string
    const _STR_TO_INT          = 13;    // force non-floating integer from formatted string (ie currency $1.00)
    const _STR_TO_UINT         = 14;    // force unsigned non-floating integer from formatted string (ie currency $1.00)
    const _STR_TO_FLOAT        = 15;    // force floating number from formatted string (ie currency $1.00)
    const _STR_TO_UFLOAT       = 16;    // force unsigned floating number from formatted string (ie currency $1.00)
    const _PHONE               = 17;    // force string as XXX-XXX-XXXX
    const _ZIP                 = 18;    // force string as 5-digit zip with optional plus four if available
    const _USTR                = 19;    // force uppercase trimmed string
    const _LSTR                = 20;    // force lowercase trimmed string
    
    const _ARRAY_BOOL          = 101;
    const _ARRAY_INT           = 102;
    const _ARRAY_UINT          = 103;
    const _ARRAY_FLOAT         = 104;
    const _ARRAY_UFLOAT        = 105;
    const _ARRAY_UNIXTIME      = 106;
    const _ARRAY_STR           = 107;
    const _ARRAY_NOTRIM        = 108;
    const _ARRAY_NOHTML        = 109;
    const _ARRAY_ARRAY         = 110;
    const _ARRAY_FILE          = 11;    // An array of "Files" behaves differently than other <input> arrays. TYPE_FILE handles both types.
    const _ARRAY_BINARY        = 112;
    const _ARRAY_STR_TO_INT    = 113;
    const _ARRAY_STR_TO_UINT   = 114;
    const _ARRAY_STR_TO_FLOAT  = 115;
    const _ARRAY_STR_TO_UFLOAT = 116;
    const _ARRAY_PHONE         = 117;
    const _ARRAY_ZIP           = 118;
    const _ARRAY_USTR          = 119;
    const _ARRAY_LSTR          = 120;
    
    const _ARRAY_KEYS_INT      = 202;
    const _ARRAY_KEYS_STR      = 207;
    
    const _CONVERT_VALUE       = 100;   // value to subtract from array types to convert to single types
    const _CONVERT_KEYS        = 200;   // value to subtract from array => keys types to convert to single types
}

