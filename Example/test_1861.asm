?Def=asm1802

init:
	ghi r0	--setup Registers 
	phi r1
	phi r2
	phi r3
	phi r4
	ldi low main
	plo r3
	ldi low stack
	plo r2
	ldi low interrupt
	plo r1
	
	sep r3 	--Goto to main loop and wait for a interrupt

return:
	ldxa
	adi 01
	ret
	
interrupt:
	dec r2
	sav
	dec r2
	str r2
	nop
	nop
	nop
	ldi 00
	phi r0
	ldi 00
	plo r0
	
refresh:
	glo r0
	sex r2
	
	sex r2
	dec r0
	plo r0
	
	sex r2
	dec r0
	plo r0
	
	sex r2
	dec r0
	plo r0
	
	bn1 low refresh
	br low return

main:
	sex r2
	inp 1   --Enable Display chip
loop:
	seq
	req
br low loop

space 003f
stack:

space 0040
db 00 00 00 00 00 00 00 00
db 00 00 00 00 00 00 00 00
db 7B DE DB DE 00 00 00 00
db 4A 50 DA 52 00 00 00 00
db 42 5E AB D0 00 00 00 00
db 4A 42 8A 52 00 00 00 00
db 7B DE 8A 5E 00 00 00 00
db 00 00 00 00 00 00 00 00
db 00 00 00 00 00 00 07 E0
db 00 00 00 00 FF FF FF FF
db 00 06 00 01 00 00 00 01
db 00 7F E0 01 00 00 00 02
db 7F C0 3F E0 FC FF FF FE
db 40 0F 00 10 04 80 00 00
db 7F C0 3F E0 04 80 00 00
db 00 3F D0 40 04 80 00 00
db 00 0F 08 20 04 80 7A 1E
db 00 00 07 90 04 80 42 10
db 00 00 18 7F FC F0 72 1C
db 00 00 30 00 00 10 42 10
db 00 00 73 FC 00 10 7B D0
db 00 00 30 00 3F F0 00 00
db 00 00 18 0F C0 00 00 00
db 00 00 07 F0 00 00 00 00

