import asyncio
import json

import websockets


URL = "ws://127.0.0.1:8000/ws/zoe"


async def main():

    print("=" * 60)
    print("🧪 PRUEBA WEBSOCKET ZOE")
    print("=" * 60)
    print()

    print(f"🔌 Conectando a {URL}...")

    async with websockets.connect(URL) as ws:

        print("✅ WebSocket conectado.")
        print()

        # ----------------------------------------------------
        # SESIÓN
        # ----------------------------------------------------

        mensaje = await ws.recv()
        datos = json.loads(mensaje)

        print("📡 Servidor:")
        print(datos)
        print()

        session_id = datos.get("session_id")

        print(f"🆔 Sesión: {session_id}")
        print()

        # ----------------------------------------------------
        # CONVERSACIÓN
        # ----------------------------------------------------

        while True:

            texto = input("👤 Tú: ").strip()

            if not texto:
                continue

            if texto.lower() in {
                "salir",
                "exit",
                "quit",
            }:
                await ws.send(
                    json.dumps({
                        "type": "close"
                    })
                )
                break

            await ws.send(
                json.dumps({
                    "type": "text",
                    "text": texto,
                })
            )

            print("🤖 ZOE está pensando...")

            respuesta = await ws.recv()
            datos = json.loads(respuesta)

            if datos.get("type") == "response":

                print()
                print(
                    f"🤖 ZOE: "
                    f"{datos.get('text', '')}"
                )
                print()

            else:

                print(
                    "📡 Servidor:",
                    datos,
                )

    print()
    print("🔌 WebSocket cerrado.")
    print("🛑 Prueba finalizada.")


if __name__ == "__main__":
    asyncio.run(main())
