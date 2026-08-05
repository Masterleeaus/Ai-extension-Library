import 'package:flutter/material.dart';
import 'package:flutter_inappwebview/flutter_inappwebview.dart';
import 'package:get/get.dart';
import 'package:qrpay/widgets/others/congratulation_widget.dart';

import '../../../backend/utils/custom_loading_api.dart';
import '../../../controller/categories/deposit/deposti_controller.dart';
import '../../../language/english.dart';
import '../../../routes/routes.dart';
import '../../../widgets/appbar/appbar_widget.dart';

class PagaditoWebPaymentScreen extends StatelessWidget {
  PagaditoWebPaymentScreen({super.key});

  final controller = Get.put(DepositController());

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: true,
      onPopInvokedWithResult: (bool didPop, Object? result) {
        if (didPop) {
          Get.offAllNamed(Routes.bottomNavBarScreen);
        }
      },
      child: Scaffold(
        appBar: AppBarWidget(
          homeButtonShow: false,
          text: Strings.pagaditoPayment,
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
    final data = controller.addMoneyPagaditoInsertModel.data;
    var paymentUrl = data.url;

    return InAppWebView(
      initialUrlRequest: URLRequest(url: WebUri(paymentUrl)),
      onWebViewCreated: (InAppWebViewController controller) {},
      onProgressChanged: (InAppWebViewController controller, int progress) {},
      onLoadStop: (controller, url) {
        if (url.toString().contains('success/response')) {
          StatusScreen.show(
            context: context,
            subTitle: Strings.addMoneySuccessfully,
            onPressed: () {
              Get.offAllNamed(Routes.bottomNavBarScreen);
            },
          );
        }
      },
    );
  }
}
