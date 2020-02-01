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
    }
}
