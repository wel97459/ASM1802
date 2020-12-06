using System;

namespace Helper
{
    public class Console
    {
        public void Write(string str, params object[] args)
        {
            System.Console.Write(str, args);
        }
        public void WriteLine(string str, params object[] args)
        {
            System.Console.WriteLine(str, args);
        }

        public void WriteBinaryFile(string fileName, string data){
            byte[] binOut = new byte[data.Length / 2];
            for(int i=0; i < data.Length / 2; i++){
                binOut[i] = (Byte)Convert.ToUInt32(data.Substring(i * 2, 2), 16);
            }
            System.IO.File.WriteAllBytes(@fileName, @binOut);
        }
    }
}
