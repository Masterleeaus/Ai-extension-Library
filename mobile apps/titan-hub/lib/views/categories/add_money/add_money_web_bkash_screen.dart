// ignore_for_file: deprecated_member_use

import 'package:flutter/material.dart';
import 'package:flutter_inappwebview/flutter_inappwebview.dart';
import 'package:get/get.dart';

import '../../../backend/utils/custom_loading_api.dart';
import '../../../controller/categories/deposit/deposti_controller.dart';
import '../../../language/english.dart';
import '../../../routes/routes.dart';
import '../../../widgets/appbar/appbar_widget.dart';
import '../../../widgets/others/congratulation_widget.dart';

class BkashWebPaymentScreen extends StatelessWidget {
  BkashWebPaymentScreen({super.key});

  final controller = Get.put(DepositController());

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: true,
      onPopInvoked: (value) {
        Get.offAllNamed(Routes.bottomNavBarScreen);
      },
      child: Scaffold(
        appBar: AppBarWidget(
          homeButtonShow: false,
          text: Strings.bkashPayment,
          onTapLeading: () {
            Get.offAllNamed(Routes.bottomNavBarScreen);
          },
        ),
        body: Obx(
          () => controller.isLoading
              ? const CustomLoadingAPI()
              : _bodyWidget(context),
        ),
      ),
    );
  }

  _bodyWidget(BuildContext context) {
    final data = controller.addMoneyBkashInsertModel.data;
    var paymentUrl = data.url;

    return InAppWebView(
      initialUrlRequest: URLRequest(url: WebUri(paymentUrl)),
      onWebViewCreated: (InAppWebViewController controller) {},
      onProgressChanged: (InAppWebViewController controller, int progress) {},   
      onLoadStop: (controller, url) {
        if (url.toString().contains('/success')) {
          StatusScreen.show(
            context: context,
            subTitle: Strings.yourMoneyAddedSucces.tr,
            onPressed: () {
              Get.offAllNamed(Routes.bottomNavBarScreen);
            },
          );
        }
      },
    );
  }
}
