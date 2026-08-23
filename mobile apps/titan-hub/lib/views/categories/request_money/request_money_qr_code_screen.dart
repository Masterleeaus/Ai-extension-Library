import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:qr_code_scanner/qr_code_scanner.dart';

import '../../../controller/categories/request_money/request_money_controller.dart';
import '../../../custom_assets/assets.gen.dart';
import '../../../language/english.dart';
import '../../../routes/routes.dart';
import '../../../utils/custom_color.dart';
import '../../../utils/dimensions.dart';
import '../../../utils/responsive_layout.dart';
import '../../../widgets/appbar/appbar_widget.dart';
import '../../others/custom_image_widget.dart';

class RequestMoneyQRCodeScreen extends StatefulWidget {
  const RequestMoneyQRCodeScreen({
    super.key,
  });

  @override
  ScanScreenState createState() => ScanScreenState();
}

class ScanScreenState extends State<RequestMoneyQRCodeScreen> {
  final qrKey = GlobalKey(debugLabel: 'QR');
  QRViewController? controller;
  Barcode? barcode;
  RxBool isVisible = true.obs;
  final requestMoneyController = Get.put(RequestMoneyController());

  @override
  void dispose() {
    controller?.dispose();
    super.dispose();
  }

  @override
  void reassemble() async {
    super.reassemble();
    if (Platform.isAndroid) {
      await controller!.pauseCamera();
    } else if (Platform.isIOS) {
      controller!.resumeCamera();
    }
  }

  void readQr() async {
    if (barcode != null) {
      controller!.pauseCamera();
      controller!.dispose();
    }
  }

  @override
  Widget build(BuildContext context) {
    SystemChrome.setSystemUIOverlayStyle(
      const SystemUiOverlayStyle(
          statusBarColor: CustomColor.primaryLightScaffoldBackgroundColor),
    );
    return ResponsiveLayout(
      mobileScaffold: Scaffold(
        appBar: const AppBarWidget(
          text: Strings.scanQR,
        ),
        body: _bodyWidget(context),
      ),
    );
  }

  // body widget containing all widget elements
  _bodyWidget(BuildContext context) {
    return Center(
      child: Stack(
        alignment: Alignment.center,
        children: [
          Positioned(
            top: 40,
            child: _scanQrCodeWidget(context),
          ),
          Positioned(
            bottom: 20,
            right: 5,
            left: 5,
            child: _iconButtonWidget(context),
          ),
        ],
      ),
    );
  }

  _scanQrCodeWidget(BuildContext context) {
    return SizedBox(
      width: MediaQuery.of(context).size.width,
      height: MediaQuery.of(context).size.height,
      child: _buildQrViewWidget(context),
    );
  }

  _buildQrViewWidget(BuildContext context) {
    return QRView(
      key: qrKey,
      onQRViewCreated: onQRViewCreated,
      overlay: QrScannerOverlayShape(
          cutOutSize: MediaQuery.of(context).size.width * 0.6,
          borderWidth: 8,
          borderLength: 20,
          borderRadius: 10,
          borderColor: CustomColor.primaryLightColor),
    );
  }

  void onQRViewCreated(QRViewController? controller) {
    setState(() => this.controller = controller);
    controller!.scannedDataStream.listen((barcode) => setState(() {
          this.barcode = barcode;
          requestMoneyController.getCheckUserWithQrCodeData(
            this.barcode!.code.toString(),
          );
          debugPrint(this.barcode!.code);

          requestMoneyController.copyInputController.text = this.barcode!.code!;
          Get.toNamed(Routes.requestMoneyScreen);

          readQr();
        }));
  }

  _iconButtonWidget(BuildContext context) {
    return Container(
      padding: EdgeInsets.symmetric(horizontal: Dimensions.paddingSize * 0.8),
      margin: EdgeInsets.only(
        bottom: Dimensions.marginSizeVertical,
      ),
      child: Row(mainAxisAlignment: MainAxisAlignment.spaceAround, children: [
        GestureDetector(
          onTap: () {
            // Get.toNamed(Routes.moneyTransferScreen);
          },
          child: CircleAvatar(
            radius: 30,
            backgroundColor: Colors.black,
            child: CustomImageWidget(path: Assets.icon.scan),
          ),
        ),
      ]),
    );
  }
}
