class PhpException : Exception
{
    PhpException($data): base($data.message){
    }
    [string]ToString(){
    return "aa";
    }
}