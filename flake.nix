{
  description = "Spenderella Development Environment";

  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/nixos-24.11";
  };

  outputs = {
    self,
    nixpkgs,
  }: let
    system = "x86_64-linux";
    pkgs = import nixpkgs {inherit system;};
  in {
    devShells.${system}.default = pkgs.mkShell {
      packages = with pkgs; [
        php84 # 8.2
        php84Packages.composer
      ];

      shellHook = ''
        alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'
      '';
    };
  };
}
