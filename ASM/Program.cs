using System;
using System.Reflection;
using Pchp.Core;
using Pchp.Core.Reflection;
using Pchp.Core.Utilities;

namespace ASM
{
    class Program
    {
        static void Main(string[] args)
        {
            var asm = new ASM1802_Class.ASM1802();
            if (args.Length > 0)
            {
                asm.main(args[0]);

                var name = asm.getFilename();
                if(args.Length > 2)
                {
                    asm.setFilename(args[2] + name);
                }
                if (args.Length > 1)
                {
                    if (args[1].ToLower() == "bin")
                    {
                        asm.getOutputBin();
                    }
                    if (args[1].ToLower() == "hex")
                    {
                        asm.getOutputHex();
                    }
                    if (args[1].ToLower() == "cof")
                    {
                        asm.getOutputCOF();
                    }
                    if (args[1].ToLower() == "vhdl")
                    {
                        asm.getOutputVHDL();
                    }
                    if (args[1].ToLower() == "mem")
                    {
                        asm.getOutputMem();
                    }
                } else asm.getOutputBin();

            } else
            {
                Console.WriteLine("ASM <Input File> <bin\\hex\\cof\\vhdl\\mem>");
            }
        }
    }
}
